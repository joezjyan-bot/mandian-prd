<?php

namespace App\Services\Calculator;

use App\Models\CalculatorConfig;
use App\Models\PriceSnapshot;
use Illuminate\Support\Str;

/**
 * 办单助手算价服务。
 * 按后台配置算出首期实付、每期应付、每期购买参考价，生成报价快照。
 * 金额全程整数“分”，不用浮点。
 *
 * 蛝本：GitHub joezjyan-bot/calculator 的 phone-rent 目录。
 * 默认规则先同步到三种方案。
 */
class CalculatorService
{
    /**
     * 试算。
     *
     * @param array $input 含 device_value_cents, periods, down_payment_ratio,
     *                     deposit_cents, selected_services(array of service id)
     * @return array 算价结果
     */
    public function quote(CalculatorConfig $config, array $input): array
    {
        $deviceValue = (int) $input['device_value_cents'];
        $periods = (int) $input['periods'];
        $downRatio = (int) ($input['down_payment_ratio'] ?? 0);
        $deposit = (int) ($input['deposit_cents'] ?? 0);

        // 首付（设备价 × 首付成数）
        $downPayment = intdiv($deviceValue * $downRatio, 100);
        // 未付本金
        $financed = $deviceValue - $downPayment;

        // 费率基准
        $rateBase = $config->rate_basis === 'device_value' ? $deviceValue : $financed;
        $totalRate = intdiv($rateBase * $config->rate_bps, 10000);

        // 总租金 = 未付本金 + 费率部分
        $totalRent = $financed + $totalRate;
        // 每期应付（平摊，余数进首期）
        $periodRent = intdiv($totalRent, $periods);
        $remainder = $totalRent - $periodRent * $periods;

        // 增值服务费总和（强制勾选 + 选中的）
        $vasTotal = 0;
        $vasApplied = [];
        foreach (($config->value_added_services ?? []) as $svc) {
            $forced = $svc['force_checked'] ?? false;
            $selected = in_array($svc['id'] ?? '', $input['selected_services'] ?? [], true);
            if ($forced || $selected) {
                $vasTotal += (int) ($svc['fee_cents'] ?? 0);
                $vasApplied[] = $svc;
            }
        }

        // 首期实付 = 首付 + 首期租金（含余数）+ 保证金 + 增值服务费
        $firstPay = $downPayment + ($periodRent + $remainder) + $deposit + $vasTotal;

        // 每期购买参考价（A 口径：剩余未付租金 + 保证金）
        $buyoutByPeriod = [];
        for ($p = 1; $p <= $periods; $p++) {
            $paidRent = $periodRent * $p + ($p >= 1 ? $remainder : 0);
            $remainingRent = max(0, $totalRent - $paidRent);
            $buyoutByPeriod[$p] = $remainingRent + $deposit;
        }

        return [
            'device_value_cents' => $deviceValue,
            'down_payment_cents' => $downPayment,
            'deposit_cents' => $deposit,
            'first_pay_cents' => $firstPay,
            'period_rent_cents' => $periodRent,
            'periods' => $periods,
            'total_amount_cents' => $totalRent,
            'value_added_services' => $vasApplied,
            'buyout_by_period' => $buyoutByPeriod,
        ];
    }

    /**
     * 生成报价快照（锁死，供扫码下单）。
     */
    public function makeSnapshot(CalculatorConfig $config, array $input, array $quote): PriceSnapshot
    {
        return PriceSnapshot::create([
            'snapshot_no' => 'SNAP' . now()->format('YmdHis') . Str::upper(Str::random(4)),
            'product_id' => $config->product_id,
            'merchant_id' => $config->merchant_id,
            'store_id' => $input['store_id'] ?? null,
            'cooperation_mode' => $config->scope,
            'device_value_cents' => $quote['device_value_cents'],
            'periods' => $quote['periods'],
            'down_payment_ratio' => (int) ($input['down_payment_ratio'] ?? 0),
            'deposit_cents' => $quote['deposit_cents'],
            'first_pay_cents' => $quote['first_pay_cents'],
            'period_rent_cents' => $quote['period_rent_cents'],
            'total_amount_cents' => $quote['total_amount_cents'],
            'buyout_by_period' => $quote['buyout_by_period'],
            'value_added_services' => $quote['value_added_services'],
            'config_version_id' => $config->config_version_id,
            'status' => 'active',
        ]);
    }
}
