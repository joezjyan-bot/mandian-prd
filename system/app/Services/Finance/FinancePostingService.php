<?php

namespace App\Services\Finance;

use App\Models\PaymentFlow;
use App\Models\WalletEntry;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 四账记账服务（示范）。
 * 一笔客户支付，同时写：支付流水账 + 钱包账 + 总账分录。
 * 【幂等】同一 callback_event_id 只入账一次。
 */
class FinancePostingService
{
    /**
     * 处理一笔支付成功的记账。
     *
     * @param array $payment 含 order_id, bill_id, merchant_id, amount_cents,
     *                       channel_trade_no, callback_event_id
     * @return bool true=入账成功；false=重复回调已忽略
     */
    public function postPaymentSuccess(array $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            // 幂等检查：同一通道交易号 + 回调事件号只处理一次
            $exists = PaymentFlow::where('channel_trade_no', $payment['channel_trade_no'])
                ->where('callback_event_id', $payment['callback_event_id'])
                ->where('status', 'success')
                ->exists();
            if ($exists) {
                return false; // 重复回调，不重复入账
            }

            $amount = (int) $payment['amount_cents'];

            // 1) 支付流水账
            PaymentFlow::create([
                'bill_id' => $payment['bill_id'] ?? null,
                'order_id' => $payment['order_id'],
                'channel' => $payment['channel'] ?? 'mock',
                'channel_trade_no' => $payment['channel_trade_no'],
                'amount_cents' => $amount,
                'fee_cents' => 0,
                'status' => 'success',
                'callback_event_id' => $payment['callback_event_id'],
                'paid_time' => now(),
            ]);

            // 2) 钱包账：扣除平台服务费后入商家钱包
            $feeBps = (int) config('business.platform_service_fee_bps', 200);
            $serviceFee = intdiv($amount * $feeBps, 10000);
            $merchantShare = $amount - $serviceFee;

            WalletEntry::create([
                'merchant_id' => $payment['merchant_id'],
                'order_id' => $payment['order_id'],
                'direction' => 'in',
                'entry_type' => 'monthly_split',
                'amount_cents' => $merchantShare,
                'status' => 'completed',
                'description' => '租金分账入账',
            ]);

            // 3) 总账分录（复式记账示范：借 现金/银行；贷 应付商家 + 平台服务费收入）
            $voucher = 'V' . now()->format('YmdHis') . Str::upper(Str::random(4));
            LedgerEntry::insert([
                [
                    'voucher_no' => $voucher, 'order_id' => $payment['order_id'],
                    'account_code' => '1001', 'account_name' => '现金/银行存款',
                    'dc' => 'debit', 'amount_cents' => $amount, 'summary' => '客户支付',
                    'booked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'voucher_no' => $voucher, 'order_id' => $payment['order_id'],
                    'account_code' => '2202', 'account_name' => '应付商家款',
                    'dc' => 'credit', 'amount_cents' => $merchantShare, 'summary' => '商家应得',
                    'booked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'voucher_no' => $voucher, 'order_id' => $payment['order_id'],
                    'account_code' => '6001', 'account_name' => '平台服务费收入',
                    'dc' => 'credit', 'amount_cents' => $serviceFee, 'summary' => '平台服务费',
                    'booked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ],
            ]);

            return true;
        });
    }
}
