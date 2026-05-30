<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Bill;
use App\Services\External\Contracts\EsignServiceInterface;
use App\Services\External\Contracts\PaymentServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 下单与订单生命周期编排。演示流程:
 * 创建订单 → 生成首期+保证金账单 → 发起签约(模拟) → 发起首期支付(模拟)。
 * 后续支付成功回调由 PaymentCallbackController 处理(见四账记账)。
 */
class OrderService
{
    public function __construct(
        private EsignServiceInterface $esign,
        private PaymentServiceInterface $payment,
    ) {}

    /**
     * 创建订单(下单确认页提交)。
     * @param array $data 已通过办单助手报价快照校验的下单数据
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_no'            => 'YZZ' . date('ymd') . Str::upper(Str::random(8)),
                'customer_id'         => $data['customer_id'],
                'merchant_id'         => $data['merchant_id'],
                'store_id'            => $data['store_id'] ?? null,
                'product_id'          => $data['product_id'],
                'cooperation_mode'    => $data['cooperation_mode'],
                'biz_line'            => $data['biz_line'] ?? 'long_rent',
                'device_value_cents'  => $data['device_value_cents'],
                'deposit_cents'       => $data['deposit_cents'],
                'first_payment_cents' => $data['first_payment_cents'],
                'period_payment_cents' => $data['period_payment_cents'],
                'periods'             => $data['periods'],
                'min_service_period_months' => config('business.min_service_period_months', 3),
                'status'              => 'created',
                'order_snapshot'      => $data['snapshot'] ?? null,
                'quote_snapshot_id'   => $data['quote_snapshot_id'] ?? null,
            ]);

            // 首期账单 = 首期应付 + 保证金(各记一条,便于四账追溯)
            Bill::create([
                'order_id' => $order->id, 'period_no' => 0, 'bill_type' => 'first',
                'amount_due_cents' => $order->first_payment_cents, 'status' => 'unpaid',
                'due_time' => now(),
            ]);
            if ($order->deposit_cents > 0) {
                Bill::create([
                    'order_id' => $order->id, 'period_no' => 0, 'bill_type' => 'deposit',
                    'amount_due_cents' => $order->deposit_cents, 'status' => 'unpaid',
                    'due_time' => now(),
                ]);
            }

            return $order;
        });
    }

    /** 发起合同签署(走模拟或真实,由 Provider 绑定决定) */
    public function startSigning(Order $order): array
    {
        $order->update(['status' => 'contract_signing']);

        return $this->esign->createSignFlow([
            'order_id'      => $order->id,
            'template_code' => 'rental_agreement_v4',
            'signer'        => ['customer_id' => $order->customer_id],
            'snapshot'      => $order->order_snapshot,
        ]);
    }

    /** 发起首期支付 */
    public function startFirstPayment(Order $order): array
    {
        $order->update(['status' => 'paying']);
        $firstBill = Bill::where('order_id', $order->id)->where('bill_type', 'first')->first();

        return $this->payment->createPayment([
            'bill_id'      => $firstBill?->id,
            'order_id'     => $order->id,
            'amount_cents' => (int) $order->first_payment_cents + (int) $order->deposit_cents,
            'subject'      => "订单 {$order->order_no} 首期+保证金",
        ]);
    }

    /**
     * 到期三选一。client 必须主动选,系统绝不默认"购买"。
     * @param string $choice return|renew|buyout
     */
    public function handleExpiryChoice(Order $order, string $choice): array
    {
        return match ($choice) {
            'return' => $this->handleReturn($order),
            'renew'  => $this->handleRenew($order),
            'buyout' => ['next' => 'buyout_confirm', 'note' => '跳独立确认页,见 BuyoutController'],
            default  => throw new \InvalidArgumentException('无效选择'),
        };
    }

    private function handleReturn(Order $order): array
    {
        $order->update(['status' => 'returning']);
        // TODO[团队]: 生成归还检测单 + 预结算(见 PRD 提前归还/归还检测)
        return ['next' => 'return_inspection'];
    }

    private function handleRenew(Order $order): array
    {
        $order->update(['status' => 'renewing']);
        // TODO[团队]: 按续租方案生成新账单计划(无需再付保证金)
        return ['next' => 'renew_plan'];
    }
}
