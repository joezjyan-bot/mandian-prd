<?php

namespace App\Services\Order;

use App\Contracts\EsignContract;
use App\Contracts\PaymentContract;
use App\Models\Order;
use App\Services\Finance\FinancePostingService;
use App\Services\Bill\BillPlanService;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 订单编排服务:下单 / 签约 / 首期支付 / 账单计划生成。
 * 依赖接口(EsignContract/PaymentContract),不感知 mock/real。
 *
 * 状态依据:全局/02 状态字典 §0.1 + §5.1;账单时机依据 办单助手02 §8。
 * §8 流程:报价快照 → 客户扫码确认 → 订单创建(冻结快照) → 生成首期支付单
 *         → 审核通过后生成账单计划 → 客户支付/代扣 → 账单结清触发分账。
 * 关键:账单计划在"审核通过后"生成,不在下单时生成(§8)。
 */
class OrderService
{
    public function __construct(
        private EsignContract $esign,
        private PaymentContract $payment,
        private FinancePostingService $finance,
        private BillPlanService $billPlan,
    ) {}

    /**
     * 下单:创建订单并冻结报价快照。
     * 文档 §8:下单时只创建订单(冻结快照),不生成账单计划。
     * 客户扫码进入,初始进入 PENDING_CUSTOMER_SUBMIT(§0.1)。
     * 账单计划改由审核通过后调用 generateBillPlan() 生成(见下方方法)。
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            return Order::create([
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
                'total_amount_cents' => $data['total_amount_cents'] ?? ($data['period_rent_cents'] * $data['periods']),
                'price_snapshot_id' => $data['price_snapshot_id'] ?? null,
                'status' => OrderStatus::PENDING_CUSTOMER_SUBMIT,
            ]);
            // 注意(§8):此处不再生成账单计划;账单计划在审核通过后由 generateBillPlan() 生成。
        });
    }

    /**
     * 生成账单计划(§8:审核通过后调用)。
     * 由审核模块在订单审核通过(PENDING_REVIEW → PENDING_SIGN)后调用;
     * 当前审核模块未实现,此方法作为账单计划的唯一生成入口预留,可手动触发演示。
     * 账单逐期金额来自办单助手算价结果 quote(与报价快照同源,§1.3),不重算。
     */
    public function generateBillPlan(Order $order, array $quote, ?\DateTimeInterface $startDate = null): array
    {
        return $this->billPlan->generateForOrder($order, $quote, $startDate);
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
     *
     * 说明(§8):首期实付与后续账单分开存。首期支付金额取订单首期实付(报价快照 first_pay_cents),
     * 不依赖账单计划(账单计划此时可能尚未生成或仅含逐期租金)。
     *
     * @param int $firstPayCents 首期实付金额(来自报价快照 first_pay_cents)
     */
    public function payFirstBill(Order $order, int $firstPayCents): array
    {
        $result = $this->payment->pay([
            'order_id' => $order->id,
            'amount_cents' => $firstPayCents,
        ]);

        // 记账(幂等)
        $posted = $this->finance->postPaymentSuccess([
            'order_id' => $order->id,
            'merchant_id' => $order->merchant_id,
            'amount_cents' => $firstPayCents,
            'channel' => 'mock',
            'channel_trade_no' => $result['channel_trade_no'],
            'callback_event_id' => $result['payment_id'],
        ]);

        if ($posted) {
            $order->update(['status' => OrderStatus::PENDING_DELIVERY]);
        }

        return ['payment' => $result, 'posted' => $posted];
    }
}
