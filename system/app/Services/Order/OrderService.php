<?php

namespace App\Services\Order;

use App\Contracts\EsignContract;
use App\Contracts\PaymentContract;
use App\Models\Order;
use App\Models\Bill;
use App\Services\Finance\FinancePostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 订单编排服务：下单 / 签约 / 首期支付。
 * 依赖接口（EsignContract/PaymentContract），不感知 mock/real。
 */
class OrderService
{
    public function __construct(
        private EsignContract $esign,
        private PaymentContract $payment,
        private FinancePostingService $finance,
    ) {}

    /**
     * 下单：生成订单 + 账单计划。
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_no' => 'YZZ' . now()->format('YmdHis') . Str::upper(Str::random(4)),
                'customer_id' => $data['customer_id'],
                'merchant_id' => $data['merchant_id'],
                'store_id' => $data['store_id'] ?? null,
                'cooperation_mode' => $data['cooperation_mode'],
                'product_name' => $data['product_name'],
                'device_value_cents' => $data['device_value_cents'],
                'deposit_cents' => $data['deposit_cents'] ?? 0,
                'periods' => $data['periods'],
                'period_rent_cents' => $data['period_rent_cents'],
                'total_amount_cents' => $data['period_rent_cents'] * $data['periods'],
                'status' => 'created',
            ]);

            // 首期账单
            Bill::create([
                'order_id' => $order->id,
                'period_no' => 0,
                'bill_type' => 'first',
                'amount_due_cents' => ($data['deposit_cents'] ?? 0) + $data['period_rent_cents'],
                'status' => 'unpaid',
                'due_time' => now(),
            ]);

            // 后续租金账单
            for ($i = 1; $i < $data['periods']; $i++) {
                Bill::create([
                    'order_id' => $order->id,
                    'period_no' => $i,
                    'bill_type' => 'rent',
                    'amount_due_cents' => $data['period_rent_cents'],
                    'status' => 'unpaid',
                    'due_time' => now()->addMonths($i),
                ]);
            }

            return $order;
        });
    }

    /**
     * 签约（走模拟或真实电子签）。
     */
    public function sign(Order $order): Order
    {
        $result = $this->esign->sign([
            'order_no' => $order->order_no,
            'customer_id' => $order->customer_id,
            'product_name' => $order->product_name,
        ]);

        $order->update([
            'esign_id' => $result['sign_id'],
            'status' => 'paying',
            'signed_at' => now(),
        ]);

        return $order;
    }

    /**
     * 首期支付（走模拟或真实支付）+ 四账记账。
     */
    public function payFirstBill(Order $order): array
    {
        $bill = $order->bills()->where('period_no', 0)->firstOrFail();

        $result = $this->payment->pay([
            'order_id' => $order->id,
            'bill_id' => $bill->id,
            'amount_cents' => $bill->amount_due_cents,
        ]);

        // 记账（幂等）
        $posted = $this->finance->postPaymentSuccess([
            'order_id' => $order->id,
            'bill_id' => $bill->id,
            'merchant_id' => $order->merchant_id,
            'amount_cents' => $bill->amount_due_cents,
            'channel' => 'mock',
            'channel_trade_no' => $result['channel_trade_no'],
            'callback_event_id' => $result['payment_id'],
        ]);

        if ($posted) {
            $bill->update([
                'amount_paid_cents' => $bill->amount_due_cents,
                'status' => 'paid',
                'paid_time' => now(),
            ]);
            $order->update(['status' => 'delivering']);
        }

        return ['payment' => $result, 'posted' => $posted];
    }
}
