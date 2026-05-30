<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Bill;
use App\Support\OrderStatus;
use App\Services\Finance\BuyoutPriceCalculator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 到期三选一:归还 / 续租 / 申请购买。
 * 系统不默认替客户选择,三个动作都由客户主动触发(C端 12_在租期间 §3 同等展示)。
 *
 * 状态依据:全局/02 状态字典 §0.1 + C端 12_在租期间 §3/§4/§5。
 * - 归还、续租不单设订单主状态(§0.1 主状态机收敛;C端12 §5.1):
 *   用关联申请记录(order.return_request_id / renewal_request_id,见 C端12 §8)与
 *   lease_end_choice_log 表达;归还完成结清时由归还验收模块置 NORMAL_SETTLED;
 *   续租保持 IN_FULFILLMENT 并延长账单。
 * - 申请购买完成 → EARLY_RETAINED(C端12 §4.3,A 口径)。
 *
 * 本次范围:仅实现"申请购买"链(生成 purchase 账单 + 支付成功置 EARLY_RETAINED)。
 * 归还申请表 / 续租申请表属归还验收模块、续租模块,不在本状态机改动范围(第7条),
 * 此处只校验前置状态并留登记入口,不写尚未建表的字段。
 */
class EndOfTermService
{
    public function __construct(private BuyoutPriceCalculator $buyoutCalculator) {}

    /**
     * 发起归还(C端12 §5)。
     * 不改订单主状态(§0.1 无 RETURNING 主状态;C端12 §5.1)。
     * 归还申请记录(return_request)与预结算由归还验收模块负责落库;
     * 本方法只校验"出租中可操作"前置,作为编排入口,不写未建表字段。
     */
    public function startReturn(Order $order): Order
    {
        $this->assertFulfilling($order);
        // TODO(归还验收模块):登记 return_request 并关联 order.return_request_id(C端12 §8)。
        // 订单主状态保持 IN_FULFILLMENT,归还完成结清时由该模块置 NORMAL_SETTLED。
        return $order;
    }

    /**
     * 续租:延长账单计划(C端12 §6)。
     * 不改订单主状态(§0.1 无 RENEWING 主状态;C端12 §5.1):续租保持 IN_FULFILLMENT,
     * 仅在原账单计划后追加新一轮 rent 账单。续租价格/期数按平台政策与配置
     * (此处用原每期租金示范;实际续租价由续租模块按配置决定)。
     */
    public function renew(Order $order, int $extraPeriods): Order
    {
        $this->assertFulfilling($order);

        return DB::transaction(function () use ($order, $extraPeriods) {
            $maxPeriod = (int) $order->bills()->max('period_no');
            for ($i = 1; $i <= $extraPeriods; $i++) {
                Bill::create([
                    'order_id' => $order->id,
                    'period_no' => $maxPeriod + $i,
                    'bill_type' => 'rent',
                    'amount_due_cents' => $order->period_rent_cents,
                    'status' => 'unpaid',
                    'due_time' => now()->addMonths($i),
                ]);
            }
            $order->periods += $extraPeriods;
            $order->save();
            // TODO(续租模块):登记 renewal_request 并关联 order.renewal_request_id(C端12 §8)。
            return $order;
        });
    }

    /**
     * 申请购买:试算金额 + 生成购买账单(独立确认页后调用)。
     * 金额口径:A 口径(C端12 §4.2)= 剩余未付租金 + 保证金,取报价快照,不重算。
     * 【合规敏感】A 口径为本期开发基准,二期残值口径挂起(C端12 §4.2 注 / 开发设计22)。
     * bill_type = purchase(§6.7,原 buyout 已废弃)。
     * 注:本方法只生成购买账单并记录申请时间(purchase_applied_at),不提前改订单主状态;
     * 支付成功后由 completeBuyout() 置 EARLY_RETAINED(避免未付款先改状态)。
     */
    public function applyBuyout(Order $order): array
    {
        $this->assertFulfilling($order);
        $order->load('bills');
        $result = $this->buyoutCalculator->calculate($order);

        return DB::transaction(function () use ($order, $result) {
            $bill = Bill::create([
                'order_id' => $order->id,
                'period_no' => 9999,
                'bill_type' => 'purchase',
                'amount_due_cents' => $result['customer_pay_cents'],
                'status' => 'unpaid',
                'due_time' => now(),
            ]);
            $order->purchase_applied_at = now();
            $order->save();

            return [
                'bill_id' => $bill->id,
                'customer_pay_cents' => $result['customer_pay_cents'],
            ];
        });
    }

    /**
     * 申请购买支付成功回调:订单置 EARLY_RETAINED(C端12 §4.3 第5步)。
     * 设备下架(下架原因 purchase_request_completed)、解锁监管锁由设备/监管锁模块
     * 在后续处理(C端12 §4.3 第6/7步),不在本服务范围。
     */
    public function completeBuyout(Order $order): Order
    {
        if (! OrderStatus::canTransition($order->status, OrderStatus::EARLY_RETAINED)) {
            throw new RuntimeException("订单状态不允许完成购买:{$order->status}");
        }
        $order->status = OrderStatus::EARLY_RETAINED;
        $order->save();
        return $order;
    }

    /**
     * 三选一前置:订单须在履约中或逾期中(C端12 §1:出租中可操作)。
     */
    private function assertFulfilling(Order $order): void
    {
        if (! in_array($order->status, [OrderStatus::IN_FULFILLMENT, OrderStatus::OVERDUE], true)) {
            throw new RuntimeException("订单当前状态不允许此操作:{$order->status}");
        }
    }
}
