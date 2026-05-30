<?php

namespace App\Services\Finance;

use App\Models\Order;
use RuntimeException;

/**
 * 申请购买价计算器。
 *
 * 【合规敏感·待法务确认】
 * A 口径（一期）：购买价 = 剩余未付租金 + 保证金
 * B 口径（二期）：设备折旧余值（与剩余租金脱钩）——未实现，占位抛异常
 * 切换由 config('business.buyout_formula') 控制，业务代码不动。
 */
class BuyoutPriceCalculator
{
    /**
     * @return array{buyout_price_cents:int, deposit_offset_cents:int, customer_pay_cents:int, formula:string}
     */
    public function calculate(Order $order): array
    {
        $formula = config('business.buyout_formula', 'A');

        $buyoutPriceCents = match ($formula) {
            'A' => $this->formulaA($order),
            'B' => $this->formulaB($order),
            default => throw new RuntimeException("未知购买价口径：{$formula}"),
        };

        $depositOffset = min($order->deposit_cents, $buyoutPriceCents);
        $customerPay = max(0, $buyoutPriceCents - $depositOffset);

        return [
            'buyout_price_cents' => $buyoutPriceCents,
            'deposit_offset_cents' => $depositOffset,
            'customer_pay_cents' => $customerPay,
            'formula' => $formula,
        ];
    }

    /**
     * A 口径：剩余未付租金 + 保证金。
     * 剩余未付租金 = 未结清租金账单的应付减已付之和。
     */
    private function formulaA(Order $order): int
    {
        $remainingRent = 0;
        foreach ($order->bills as $bill) {
            if ($bill->bill_type === 'rent' && $bill->status !== 'paid') {
                $remainingRent += max(0, $bill->amount_due_cents - $bill->amount_paid_cents);
            }
        }
        return $remainingRent + $order->deposit_cents;
    }

    /**
     * B 口径：设备折旧余值。二期实现。
     */
    private function formulaB(Order $order): int
    {
        throw new RuntimeException('B口径（折旧余值）为二期功能，尚未实现。TODO[二期]');
    }
}
