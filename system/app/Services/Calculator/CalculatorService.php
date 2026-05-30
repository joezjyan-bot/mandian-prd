<?php

namespace App\Services\Calculator;

use App\Models\CalculatorConfig;
use App\Models\PriceSnapshot;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 办单助手算价服务。
 * 唯一依据:门店手机端/办单助手/02 计算器字段与账单公式表 §1.1-§1.5、§11。
 *
 * 全程以「分」为整数运算(§0 第2条),仅展示时转元;月付按分四舍五入后参与后续计算(§12)。
 *
 * 核心公式(§1.2,默认 rate_base=unpaid_x_rate):
 *   down       = price × ratio
 *   unpaid     = price − down
 *   totalRemain= unpaid × rate           (rate 查二维费率表 rates[periods][ratio],§11)
 *   remainPeriods = periods − 1
 *   monthly    = totalRemain ÷ remainPeriods   (按分四舍五入)
 *   deposit    = down − firstRent
 *   firstPay   = down + 增值服务(计入首期的)   ( = deposit + firstRent + 增值服务 )
 *   buyoutTotal= deposit + (firstRent + monthly × remainPeriods)
 * 账单(§1.3):第1期=firstRent;第2..periods期=monthly;
 *   当期留购价 = 剩余未付租金 + deposit;末期留购价 = deposit + 名义留购费(§1.4)。
 */
class CalculatorService
{
    /**
     * 试算。
     *
     * @param array $input device_value_cents, periods, down_payment_ratio(百分比整数,如30),
     *                     selected_services(array of service id)
     * @return array
     */
    public function quote(CalculatorConfig $config, array $input): array
    {
        $price = (int) $input['device_value_cents'];
        $periods = (int) $input['periods'];
        $ratioPct = (int) ($input['down_payment_ratio'] ?? 0);

        if ($periods < 2) {
            throw new RuntimeException('期数至少为 2(第1期首期租金 + 后续月付),当前:' . $periods);
        }

        $firstRent = (int) ($config->first_rent_cents ?? 1000);
        $nominalBuyoutFee = (int) ($config->nominal_buyout_fee_cents ?? 100);

        // 首付 down = price × ratio
        $down = intdiv($price * $ratioPct, 100);
        // 未付 unpaid = price − down
        $unpaid = $price - $down;

        // 后续应还总额 totalRemain,按 rate_base 口径(§1.5)
        $rate = $this->resolveRate($config, $periods, $ratioPct); // 费率倍数,万分比整数
        $rateBase = $config->rate_base ?? 'unpaid_x_rate';
        $totalRemain = match ($rateBase) {
            'unpaid_x_rate' => intdiv($unpaid * $rate, 10000),
            'price_x_rate' => intdiv($price * $rate, 10000),
            'remaining_multiplier' => intdiv($unpaid * ((int) ($config->remaining_multiplier_bps ?? 0)), 10000),
            default => throw new RuntimeException("未知 rate_base:{$rateBase}"),
        };

        // 后续期数 = periods − 1;月付按分四舍五入(§12)
        $remainPeriods = $periods - 1;
        $monthly = (int) round($totalRemain / $remainPeriods);

        // 保证金 deposit = down − firstRent(§1.2)
        $deposit = $down - $firstRent;
        if ($deposit < 0) {
            throw new RuntimeException('首付不足以覆盖首期租金,保证金为负:请检查首付成数与首期租金配置');
        }

        // 增值服务:强制勾选 + 选中;按 charge_in 区分计入首期/并入账单/单独收取(§4.1)
        $selected = $input['selected_services'] ?? [];
        $vasApplied = [];
        $vasFirstPay = 0; // 计入首期实付的增值服务合计
        foreach (($config->value_added_services ?? []) as $svc) {
            $forced = $svc['force_checked'] ?? false;
            $isSel = in_array($svc['id'] ?? $svc['service_id'] ?? '', $selected, true);
            if (! $forced && ! $isSel) {
                continue;
            }
            $amount = (int) ($svc['amount'] ?? $svc['fee_cents'] ?? 0);
            $chargeIn = $svc['charge_in'] ?? 'first_pay';
            if ($chargeIn === 'first_pay') {
                $vasFirstPay += $amount;
            }
            $vasApplied[] = ['service' => $svc, 'amount_cents' => $amount, 'charge_in' => $chargeIn];
        }

        // 首期实付 = down + 计入首期的增值服务( = deposit + firstRent + 增值服务 )
        $firstPay = $down + $vasFirstPay;

        // 逐期账单与留购价(§1.3 / §1.4)
        $billByPeriod = [];   // 每期应付
        $buyoutByPeriod = []; // 每期当期留购价
        for ($i = 1; $i <= $periods; $i++) {
            $billByPeriod[$i] = ($i === 1) ? $firstRent : $monthly;

            if ($i === $periods) {
                // 末期留购价 = deposit + 名义留购费(§1.4 合规口径)
                $buyoutByPeriod[$i] = $deposit + $nominalBuyoutFee;
            } else {
                // 当期留购价 = 该期付完后剩余未付租金 + deposit
                // 付完第 i 期后,已覆盖首期租金 + (i-1) 个 monthly;剩余 monthly 数 = remainPeriods − (i-1)
                $remainingRentMonths = $remainPeriods - ($i - 1);
                $buyoutByPeriod[$i] = $monthly * $remainingRentMonths + $deposit;
            }
        }

        // 留购总价(§1.2)= deposit + (firstRent + monthly × remainPeriods)
        $buyoutTotal = $deposit + ($firstRent + $monthly * $remainPeriods);

        return [
            'device_value_cents' => $price,
            'down_payment_cents' => $down,
            'unpaid_cents' => $unpaid,
            'rate_base' => $rateBase,
            'rate_bps' => $rate,
            'total_remain_cents' => $totalRemain,
            'period_rent_cents' => $monthly,
            'first_rent_cents' => $firstRent,
            'deposit_cents' => $deposit,
            'first_pay_cents' => $firstPay,
            'periods' => $periods,
            'remain_periods' => $remainPeriods,
            'value_added_services' => $vasApplied,
            'bill_by_period' => $billByPeriod,
            'buyout_by_period' => $buyoutByPeriod,
            'buyout_total_cents' => $buyoutTotal,
            'nominal_buyout_fee_cents' => $nominalBuyoutFee,
        ];
    }

    /**
     * 查费率倍数(万分比整数)。
     * 优先二维费率表 rate_table[periods][ratioPct](§11);缺失回落到 rate_bps 兼容值。
     */
    private function resolveRate(CalculatorConfig $config, int $periods, int $ratioPct): int
    {
        $table = $config->rate_table ?? null;
        if (is_array($table)) {
            // 键既可能是 "12"/"30",也可能是 "0.3";优先整数百分比键,再尝试小数键
            $row = $table[(string) $periods] ?? $table[$periods] ?? null;
            if (is_array($row)) {
                $ratioKeyPct = (string) $ratioPct;          // "30"
                $ratioKeyDec = rtrim(rtrim(number_format($ratioPct / 100, 2, '.', ''), '0'), '.'); // "0.3"
                $val = $row[$ratioKeyPct] ?? $row[$ratioKeyDec] ?? null;
                if ($val !== null) {
                    // 表里存倍数(如 1.26),换算万分比整数
                    return (int) round(((float) $val) * 10000);
                }
            }
        }
        // 回落:兼容旧 rate_bps(万分比)
        return (int) ($config->rate_bps ?? 0);
    }

    /**
     * 生成报价快照(锁死,供扫码下单)。§7.1。
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
            'total_amount_cents' => $quote['buyout_total_cents'],
            'buyout_by_period' => $quote['buyout_by_period'],
            'value_added_services' => $quote['value_added_services'],
            'config_version_id' => $config->config_version_id,
            'status' => 'active',
        ]);
    }
}
