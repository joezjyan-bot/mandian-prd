<?php

namespace App\Services\Bill;

use App\Models\Order;
use App\Models\Bill;
use App\Services\Calculator\CalculatorService;
use App\Models\CalculatorConfig;
use Illuminate\Support\Facades\DB;

/**
 * 账单计划生成服务。
 * 唯一依据:门店手机端/办单助手/02 §1.3(逐期账单)、§8(账单生成流程);状态字典 §6.7(bill_type)。
 *
 * 口径(§1.3):
 *   第 1 期      → bill_type=first,金额 = 首期租金 firstRent(默认 10 元)
 *   第 2..N 期   → bill_type=rent, 金额 = monthly
 * 说明:保证金 / 设备管理费 / 公证费等"计入首期实付"的部分,在首期实付一次性收取(§1.2),
 *      不重复进账单(§1.3 说明)。增值服务中 charge_in='installment'(并入账单)的才进账单。
 *
 * §8 要求:首期实付与后续账单分开存;账单计划由报价快照按 §1 公式生成。
 * 本服务直接消费 CalculatorService::quote() 的逐期结果(bill_by_period),
 * 不重新计算金额(与办单助手报价、C 端账单同源,§1.3 一一对应)。
 */
class BillPlanService
{
    public function __construct(private CalculatorService $calculator) {}

    /**
     * 用算价结果为订单生成账单计划。
     * 文档 §8:审核通过后生成账单计划;此处接受已算好的 quote(来自报价快照/CalculatorService)。
     *
     * @param Order $order 订单(须已含 periods/period_rent 等;账单按 quote 落)
     * @param array $quote CalculatorService::quote() 的返回,含 bill_by_period / value_added_services
     * @param \DateTimeInterface|null $startDate 起算日(默认 now;第1期 due=起算日,之后逐月)
     * @return Bill[] 生成的账单集合
     */
    public function generateForOrder(Order $order, array $quote, ?\DateTimeInterface $startDate = null): array
    {
        $start = $startDate ? \Illuminate\Support\Carbon::instance($startDate) : now();

        return DB::transaction(function () use ($order, $quote, $start) {
            $bills = [];
            $billByPeriod = $quote['bill_by_period'] ?? [];
            $periods = (int) ($quote['periods'] ?? $order->periods);

            // 清理该订单可能已存在的旧账单计划,避免重复生成(账单生成前可重算,§9/§0.3)
            Bill::where('order_id', $order->id)->delete();

            // 逐期账单(§1.3):第1期 first,2..N rent
            for ($i = 1; $i <= $periods; $i++) {
                $type = ($i === 1) ? 'first' : 'rent';
                $amount = (int) ($billByPeriod[$i] ?? 0);
                $due = ($i === 1) ? $start->copy() : $start->copy()->addMonths($i - 1);

                $bills[] = Bill::create([
                    'order_id' => $order->id,
                    'period_no' => $i,
                    'bill_type' => $type,
                    'amount_due_cents' => $amount,
                    'status' => 'unpaid',
                    'due_time' => $due,
                ]);
            }

            // 并入账单的增值服务(charge_in='installment'):作为独立账单项挂第1期账单日
            foreach (($quote['value_added_services'] ?? []) as $vas) {
                if (($vas['charge_in'] ?? '') !== 'installment') {
                    continue; // 计入首期实付的(first_pay)不进账单;单独收取的(standalone)另行处理
                }
                $svc = $vas['service'] ?? [];
                $billType = $this->vasBillType($svc);
                $bills[] = Bill::create([
                    'order_id' => $order->id,
                    'period_no' => 1,
                    'bill_type' => $billType,
                    'amount_due_cents' => (int) ($vas['amount_cents'] ?? 0),
                    'status' => 'unpaid',
                    'due_time' => $start->copy(),
                ]);
            }

            return $bills;
        });
    }

    /**
     * 增值服务 → bill_type(§6.7)。公证类→notary,其余增值服务→service。
     */
    private function vasBillType(array $svc): string
    {
        $name = (string) ($svc['name'] ?? '');
        if (str_contains($name, '公证')) {
            return 'notary';
        }
        return 'service';
    }
}
