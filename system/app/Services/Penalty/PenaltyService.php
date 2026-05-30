<?php

namespace App\Services\Penalty;

use App\Models\Order;
use RuntimeException;

/**
 * 逾期费用服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/11_逾期费用账单与规则配置.md(§2 生命周期、§3 数据模型、
 *           §4 计算、§5 停止累计、§7 手动操作、§9 提前结清/归还前必清、§9.4 违约赔偿边界、
 *           §10 权限、§11 异常);C端12 §7(0.05%/日、最低10元/日、全归平台不分账)。
 *
 * ⚠️ 方法体均为占位(throw 未实现)。数据库字段/路径保留 penalty(§3 重要原则),UI 展示"逾期费用"。
 *
 * —— 关键业务/合规约束(务必遵守)——
 * 1. 逾期费用是【独立账单】(order_penalty),与主账单两条线并行;不付逾期费用也可付主账单(§1)。
 * 2. 规则【快照】写入 order_penalty.rule_snapshot;后续改规则不影响已生效订单(§3 重要原则、§11)。
 * 3. 全归平台收入,不参与分账(C端12 §7;财务07)。
 * 4. 配置粒度:商家订单→商家自配;联营/平台订单→平台统配(§1)。
 * 5. 减免:reduced_amount 不可超过 original_amount(§11);所有手动操作必填原因+写日志(§10)。
 * 6. 提前结清/归还前【必须结清】未付逾期费用,或经运营主管全额减免(§9)。
 * 7. 【合规边界 §9.4】违约不自动触发全部未到期费用加速到期;禁止"全部未到期费用自动一次性到期"。
 *    设备灭失/拒还时赔偿按设备确认价 + 已到期欠费 + 已生成逾期费用 + 可举证实际损失,
 *    不按剩余全部未到期费用。资方特殊清算规则单独配置、不与普通渠道混判、不在 C 端暴露。
 *
 * —— 设计约定(便于接手)——
 * - order_penalty / order_penalty_reduction_log / penalty_rule_config 表(§3)由团队按文档建,骨架不擅自建表。
 * - 本服务不耦合主账单/结算服务;提前结清/归还的"必清检查"由 EndOfTermService 调本服务 check 方法编排。
 */
class PenaltyService
{
    /**
     * 计算某条逾期费用的日金额(§4.2)。
     *
     * ⚠️【精度口径待确认 — 不擅自固化】
     * 文档 §4.3 比例示例(¥568.75 × 0.05%/天)给出"日金额 ≈¥0.28、5天 ¥1.42",
     * 但 0.284 → 0.28(四舍五入)后 ×5 = ¥1.40,与 ¥1.42 差 2 分;
     * 即"先按分四舍五入日金额再×天" vs "按精确值×天再取整"两种口径结果不同。
     * 逾期费用要向客户收款,精度口径须与财务对齐后再固化(参考办单助手02 §0「全程以分整数运算」)。
     * 因此本方法不预先选定四舍五入方式,待业务团队 + 财务确认后实现。
     *
     * 公式(§4.2):
     *   fixed   : daily = rule_value(分/天)
     *   percent : daily = period_amount × rule_value%       (精度口径待定)
     *   单日最低: daily = max(daily, rule_min_per_day)
     * 入参均为分;rule_value_bps 为 percent 时的万分比、fixed 时的分/天。
     * TODO(业务团队 + 财务):确认精度口径后实现。
     */
    public function dailyAmountCents(string $ruleType, int $periodAmountCents, int $ruleValueBps, int $ruleMinPerDayCents): int
    {
        throw new RuntimeException('PenaltyService::dailyAmountCents 待实现(运营端11 §4.2;精度口径需与财务确认)');
    }

    /**
     * 定时任务:每日扫描履约中订单,生成/累计逾期费用账单(§2、§4.1)。
     * 每日凌晨执行:已逾期未生成→新建 order_penalty;已生成未结清→accumulated_days+1 重算 original_amount;
     * 总额封顶 rule_max_total(§4.2)。生成时写 rule_snapshot(§3)。
     * TODO(业务团队):实现扫描、生成、累计、封顶、快照。
     */
    public function dailyAccrual(): void
    {
        throw new RuntimeException('PenaltyService::dailyAccrual 待实现(运营端11 §4.1 定时累计)');
    }

    /**
     * 停止累计(§5)。客户付清对应主账单期/全额减免/订单关闭等触发,end_date=today。
     * TODO(业务团队):按 §5 触发条件停止累计、置状态。
     */
    public function stopAccrual(int $penaltyId, string $reason): void
    {
        throw new RuntimeException('PenaltyService::stopAccrual 待实现(运营端11 §5 停止累计)');
    }

    /**
     * 手动修改原始金额(§7.1)。运营操作,必填原因,写日志(§10 留痕)。
     * TODO(业务团队):改 original_amount、重算 final_amount、写 reduction_log/操作日志。
     */
    public function editAmount(int $penaltyId, int $newOriginalCents, string $reason, int $operatorId): void
    {
        throw new RuntimeException('PenaltyService::editAmount 待实现(运营端11 §7.1 修改原始金额)');
    }

    /**
     * 手动减免(§7.2)。reduced_amount 累加,且不可超过 original_amount(§11)。
     * final_amount = original - reduced;写 order_penalty_reduction_log(原因/操作人/角色/IP)。
     * TODO(业务团队):校验不超额、累加减免、记减免日志、C端同步展示"已减免(运营调整)"。
     */
    public function reduce(int $penaltyId, int $reductionCents, string $reason, int $operatorId): void
    {
        throw new RuntimeException('PenaltyService::reduce 待实现(运营端11 §7.2 手动减免)');
    }

    /**
     * 全额减免/清零(§7.3)。状态→waived,不再累计。单期客服可操作,批量(全订单)仅运营主管(§10)。
     * TODO(业务团队):置 waived、按权限校验、记日志。
     */
    public function waive(int $penaltyId, string $reason, int $operatorId): void
    {
        throw new RuntimeException('PenaltyService::waive 待实现(运营端11 §7.3 全额减免)');
    }

    /**
     * 提前结清/归还前的逾期费用必清检查(§9)。
     * 返回未结清逾期费用合计;供 EndOfTermService 在申请购买/归还时编排:
     *   有未清 → 要求一并结清或先付逾期费用;经运营主管全额减免(§9.3)则跳过。
     * ⚠️ 合规(§9.4):此处只汇总"已到期欠费 + 已生成逾期费用 + 可举证实际损失",
     *    不得把剩余全部未到期费用计入(禁止加速到期)。
     * TODO(业务团队):汇总未结清逾期费用,返回明细供编排。
     */
    public function outstandingForSettlement(Order $order): array
    {
        throw new RuntimeException('PenaltyService::outstandingForSettlement 待实现(运营端11 §9 必清检查;守§9.4不加速到期)');
    }
}
