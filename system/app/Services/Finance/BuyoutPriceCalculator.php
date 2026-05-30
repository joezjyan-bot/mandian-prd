<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\Bill;

/**
 * 申请购买价计算。
 *
 * ⚠️⚠️ 合规敏感,待法务确认 ⚠️⚠️
 * 当前 A 口径:购买价 = 剩余应付租金 + 保证金。
 * A 口径系统现成、可马上开发,但"累计租金≈设备价"易被认定为变相分期买断。
 * 二期 B 口径(折旧余值,与剩余租金脱钩)切换时:只改本类,不动下单/到期/支付流程。
 *
 * 切换由 config('business.buyout_formula') 控制(A / B)。
 */
class BuyoutPriceCalculator
{
    /**
     * @return array ['buyout_price_cents'=>int,'deposit_offset_cents'=>int,'customer_pay_cents'=>int,'formula'=>string]
     */
    public function calculate(Order $order): array
    {
        $formula = config('business.buyout_formula', 'A');

        $buyout = match ($formula) {
            'A' => $this->formulaA($order),
            'B' => $this->formulaB($order),
            default => $this->formulaA($order),
        };

        $deposit = (int) $order->deposit_cents;
        // 保证金抵扣购买价;若保证金更高则实付为 0(差额另行退还,见 WalletService)
        $customerPay = max(0, $buyout - $deposit);

        return [
            'buyout_price_cents'  => $buyout,
            'deposit_offset_cents' => min($deposit, $buyout),
            'customer_pay_cents'  => $customerPay,
            'formula'             => $formula,
        ];
    }

    /** A 口径:剩余应付租金 + 保证金 */
    private function formulaA(Order $order): int
    {
        $remainingRent = (int) Bill::where('order_id', $order->id)
            ->where('bill_type', 'rent')
            ->whereIn('status', ['unpaid', 'part_paid'])
            ->sum(\DB::raw('amount_due_cents - amount_paid_cents'));

        return $remainingRent + (int) $order->deposit_cents;
    }

    /**
     * B 口径:折旧余值(二期)。
     * TODO[团队/财务/法务]: 实现折旧余值算法,与剩余租金脱钩。
     * 例:残值 = 设备价值 × 残值率 或 设备价值 × (1 - 月折旧率 × 已用月数),取保底。
     */
    private function formulaB(Order $order): int
    {
        // TODO[团队]: 二期实现。当前抛异常以防误用。
        throw new \RuntimeException('购买价 B 口径(折旧余值)待二期实现,需法务/财务确认');
    }
}
