<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Bill;
use App\Support\OrderStatus;
use App\Services\Finance\BuyoutPriceCalculator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 到期三选一：归还 / 续租 / 申请购买。
 * 系统不默认替客户选择，三个动作都由客户主动触发。
 */
class EndOfTermService
{
    public function __construct(private BuyoutPriceCalculator $buyoutCalculator) {}

    /** 发起归还 */
    public function startReturn(Order $order): Order
    {
        $this->assertActive($order);
        $order->status = OrderStatus::RETURNING;
        $order->save();
        return $order;
    }

    /** 续租：生成新一期账单（示范，不重复收保证金） */
    public function renew(Order $order, int $extraPeriods): Order
    {
        $this->assertActive($order);

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
            return $order;
        });
    }

    /**
     * 申请购买：试算金额 + 生成购买账单（独立确认页后调用）。
     * 【合规敏感】A 口径待法务确认，口径可切换。
     */
    public function applyBuyout(Order $order): array
    {
        $this->assertActive($order);
        $order->load('bills');
        $result = $this->buyoutCalculator->calculate($order);

        return DB::transaction(function () use ($order, $result) {
            $bill = Bill::create([
                'order_id' => $order->id,
                'period_no' => 9999,
                'bill_type' => 'buyout',
                'amount_due_cents' => $result['customer_pay_cents'],
                'status' => 'unpaid',
                'due_time' => now(),
            ]);
            $order->status = OrderStatus::BUYING_OUT;
            $order->save();

            return [
                'bill_id' => $bill->id,
                'customer_pay_cents' => $result['customer_pay_cents'],
            ];
        });
    }

    private function assertActive(Order $order): void
    {
        if (! in_array($order->status, [OrderStatus::ACTIVE, OrderStatus::OVERDUE], true)) {
            throw new RuntimeException("订单当前状态不允许此操作：{$order->status}");
        }
    }
}
