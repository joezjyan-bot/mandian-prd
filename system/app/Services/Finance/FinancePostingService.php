<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\Bill;
use App\Models\PaymentFlow;
use App\Models\WalletEntry;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

/**
 * 支付成功后的四账记账。核心要求:可追溯、可审计、可回滚、可幂等。
 *
 * 幂等:以 callback_event_id 唯一约束保证同一回调只入账一次。
 * 四账:支付流水账(payment_flows)、订单业务账(bills/orders)、
 *       钱包账(wallet_entries)、总账分录(ledger_entries)同一事务内一致更新。
 */
class FinancePostingService
{
    /**
     * 处理一笔支付成功。
     * @param array $parsed PaymentServiceInterface::parseCallback 的结果
     */
    public function onPaymentSuccess(array $parsed): array
    {
        return DB::transaction(function () use ($parsed) {
            // 幂等:event_id 已处理过则直接返回(唯一索引兜底)
            $exists = PaymentFlow::where('callback_event_id', $parsed['event_id'])->first();
            if ($exists) {
                return ['idempotent' => true, 'payment_flow_id' => $exists->id];
            }

            $flow = PaymentFlow::where('pay_flow_id', $parsed['pay_flow_id'])->firstOrFail();
            $order = Order::findOrFail($flow->order_id);

            // 1) 支付流水账:置成功 + 记幂等键
            $flow->update([
                'status'            => 'success',
                'channel_trade_no'  => $parsed['channel_trade_no'],
                'callback_event_id' => $parsed['event_id'],
                'paid_time'         => now(),
            ]);

            // 2) 订单业务账:更新对应账单已付
            $this->markBillsPaid($order, $parsed['amount_cents']);

            // 3) 分账 → 钱包账(按合作模式)+ 4) 总账分录
            $this->splitAndPost($order, $flow, $parsed['amount_cents']);

            // 推进订单状态(首期付完 → 待交付)
            if ($order->status === 'paying') {
                $order->update(['status' => 'delivering']);
            }

            return ['idempotent' => false, 'payment_flow_id' => $flow->id];
        });
    }

    private function markBillsPaid(Order $order, int $amountCents): void
    {
        // 简化:按 due 顺序冲账。TODO[团队]: 处理部分支付/超额/指定账单的精细规则。
        $remaining = $amountCents;
        $bills = Bill::where('order_id', $order->id)
            ->whereIn('status', ['unpaid', 'part_paid'])
            ->orderBy('period_no')->get();

        foreach ($bills as $bill) {
            if ($remaining <= 0) break;
            $need = $bill->amount_due_cents - $bill->amount_paid_cents;
            $pay  = min($need, $remaining);
            $bill->amount_paid_cents += $pay;
            $bill->status = $bill->amount_paid_cents >= $bill->amount_due_cents ? 'paid' : 'part_paid';
            if ($bill->status === 'paid') $bill->paid_time = now();
            $bill->save();
            $remaining -= $pay;
        }
    }

    /**
     * 分账 + 总账。按合作模式拆平台服务费与商家份额。
     * TODO[团队/财务]: 联营/平台订单的拆分比例、内部台账按 PRD 财务12 精化;科目编码待财务定。
     */
    private function splitAndPost(Order $order, PaymentFlow $flow, int $amountCents): void
    {
        $feeBps = (int) config('business.platform_service_fee_bps', 200);
        $serviceFee = intdiv($amountCents * $feeBps, 10000);
        $merchantShare = $amountCents - $serviceFee;

        // 总账:平台服务费收入(credit)
        LedgerEntry::create([
            'subject_code' => 'PLATFORM_SERVICE_FEE', 'direction' => 'credit',
            'amount_cents' => $serviceFee, 'order_id' => $order->id,
            'payment_flow_id' => $flow->id, 'cooperation_mode' => $order->cooperation_mode,
            'memo' => '平台服务费',
        ]);

        // 商家订单 / 商家自营:商家份额进钱包
        if ($order->cooperation_mode === 'self_operate') {
            $this->addWallet($order->merchant_id, 'monthly_split', $merchantShare, $order->id, '客户支付分账(扣服务费后)');
        } else {
            // 联营/平台:TODO[团队] 按约定比例进商家钱包/平台台账/内部台账
            LedgerEntry::create([
                'subject_code' => 'PLATFORM_RECEIVABLE', 'direction' => 'debit',
                'amount_cents' => $merchantShare, 'order_id' => $order->id,
                'payment_flow_id' => $flow->id, 'cooperation_mode' => $order->cooperation_mode,
                'memo' => '联营/平台订单份额(待团队按比例拆分)',
            ]);
        }
    }

    private function addWallet(int $merchantId, string $type, int $amountCents, int $orderId, string $desc): void
    {
        $last = WalletEntry::where('merchant_id', $merchantId)->latest('id')->first();
        $before = $last?->balance_after_cents ?? 0;
        WalletEntry::create([
            'merchant_id' => $merchantId, 'direction' => 'in', 'entry_type' => $type,
            'amount_cents' => $amountCents, 'order_id' => $orderId,
            'balance_before_cents' => $before, 'balance_after_cents' => $before + $amountCents,
            'status' => 'completed', 'description' => $desc,
        ]);
    }
}
