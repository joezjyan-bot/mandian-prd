<?php

namespace App\Services\Order;

use App\Contracts\EsignContract;
use App\Contracts\PaymentContract;
use App\Models\Order;
use App\Models\Bill;
use App\Services\Finance\FinancePostingService;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 订单编排服务:下单 / 签约 / 首期支付。
 * 依赖接口(EsignContract/PaymentContract),不感知 mock/real。
 *
 * 状态依据:全局/02 状态字典 §0.1 + §5.1 流转图。
 * 注:§5.1 完整长租前置链路为
 *   DRAFT → PENDING_CUSTOMER_SUBMIT → PENDING_REVIEW →(PENDING_SUPPLEMENT/REVIEW_REJECTED)
 *   → PENDING_SIGN →(SIGN_FAILED)→ PENDING_FIRST_PAYMENT →(FIRST_PAYMENT_FAILED)
 *   → PENDING_DELIVERY → PENDING_RECEIPT_CONFIRM → PENDING_LOCK_VERIFY
 *   → PENDING_PLATFORM_SETTLEMENT → IN_FULFILLMENT
 * 本服务实现下单、签约、首付三段;风控审核(PENDING_REVIEW)、补资料、签约失败重试等
 * 环节由对应模块按 §5.1 补充,此处不省略状态、也不擅自合并审核流程。
 */
class OrderService
{
    public function __construct(
        private EsignContract $esign,
        private PaymentContract $payment,
        private FinancePostingService $finance,
    ) {}

    /**
     * 下单:生成订单 + 账单计划。
     * 文档 §0.1:草稿(DRAFT)由商家/运营创建;客户扫码进入待提交资料(PENDING_CUSTOMER_SUBMIT)。
     * 本方法生成的是已带客户信息的订单,初始进入 PENDING_CUSTOMER_SUBMIT。
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
                'status' => OrderStatus::PENDING_CUSTOMER_SUBMIT,
            ]);

            // 首期账单(bill_type 枚举见 §6.7:first/rent/service/notary/purchase/diff)
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
     * 签约(走模拟或真实电子签)。
     * 文档 §5.1:PENDING_SIGN →(成功)→ PENDING_FIRST_PAYMENT;失败进 SIGN_FAILED。
     * 前置应为审核通过(PENDING_REVIEW→PENDING_SIGN),由审核模块负责置位。
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
            'status' => OrderStatus::PENDING_FIRST_PAYMENT,
            'signed_at' => now(),
        ]);

        return $order;
    }

    /**
     * 首期支付(走模拟或真实支付)+ 四账记账。
     * 文档 §5.1:PENDING_FIRST_PAYMENT →(成功)→ PENDING_DELIVERY;失败进 FIRST_PAYMENT_FAILED。
     */
    public function payFirstBill(Order $order): array
    {
        $bill = $order->bills()->where('period_no', 0)->firstOrFail();

        $result = $this->payment->pay([
            'order_id' => $order->id,
            'bill_id' => $bill->id,
            'amount_cents' => $bill->amount_due_cents,
        ]);

        // 记账(幂等)
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
            $order->update(['status' => OrderStatus::PENDING_DELIVERY]);
        }

        return ['payment' => $result, 'posted' => $posted];
    }
}
