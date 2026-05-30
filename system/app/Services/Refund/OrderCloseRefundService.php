<?php

namespace App\Services\Refund;

use App\Models\Order;
use RuntimeException;

/**
 * 订单关闭 / 退款 / 售后服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/05_订单关闭退款与售后.md(§4 关闭、§5 退款、§6 冲正、
 *           §8 归还与留购、§9 权限);对接 10_订单撤单与补充合同 §3、财务管理/07 §6。
 *
 * ⚠️ 方法体均为占位(throw 未实现),不预先写死实现。
 *
 * —— 核心口径(§2,务必遵守)——
 * 1. 关闭/退款/冲正/售后都是高风险动作:必须权限 + 二次确认(多为 2FA)+ 原因 + 日志(§2.1、§9)。
 * 2. 不能只改订单状态;涉及钱必须生成财务流水/退款单/冲正单/冻结记录(§2.2)。
 * 3. 已签合同/已支付/已发货/【已分账】后的关闭退款,必须走严格退款工单流程(§2.5、§5.5)。
 * 4. 商家订单由商家/门店自管售后,平台只监管+异常介入;联营/平台订单运营端主控(§2.3、§2.4)。
 *
 * —— 退款路径判断(§5.3,核心)——
 *   计算可退金额(扣撤单费用)→ 判断是否已对外分账:
 *     未分账 → 简单退款(平台对公直接原路退,§5.4)
 *     已分账 → 退款工单(从门店追回 → 退客户 → 内部清算,§5.5;不可原路退)
 *
 * —— 冲正(§6)——
 * 冲正不能删除原流水,只能生成反向流水(refund_deduction);
 * 内部资金来源台账/授信额度按 refunded_amount 还原(§6,合规敏感:资方内部清算属后台,不在 C 端暴露)。
 *
 * —— 留购/提前结清(§8.2)——
 * purchase_price 用 A 口径(剩余应付租金 + 保证金,与办单助手02/C端12 一致;已由 BuyoutPriceCalculator 实现);
 * 二期折旧余值口径【挂起】,不在此实现。legal_nature 按 funding_source 决定(留购/提前结清,内部区分);
 * 检查:当期已付清 + 之前期已付清(严格顺序)+ 无未结清逾期费用(调 PenaltyService::outstandingForSettlement)。
 *
 * —— 设计约定(便于接手)——
 * - refund_application / refund_workflow / early_return_request / return_inspection_report /
 *   return_fee_item / purchase_requests 等表(§5.2/§8.1.2/§8.1.3/§11)由团队按文档建,骨架不擅自建表。
 * - 退款工单的内部清算/资方台账还原依赖财务07、资方管理文档,骨架不实现资方逻辑,仅预留调用位。
 * - 本服务不直接耦合 FinancePostingService 记账细节;退款记账由 Controller/财务服务编排。
 */
class OrderCloseRefundService
{
    /**
     * 关闭订单(§4)。
     * 校验可关闭场景(§4.1/§4.2):未付款可直接关;已发货未签收→售后取消;履约中不可普通关闭;
     * 已分账→强制退款工单。关闭弹窗动作(§4.3):释放库存/释放内部资金来源额度/触发退款。
     * 已付款关闭需主管 2FA(§9)。
     * TODO(业务团队):场景校验、状态流转(经 OrderStatus::canTransition 到 CLOSED/CANCELLED)、
     *      联动释放库存/额度/退款判断、写操作日志。
     */
    public function closeOrder(Order $order, string $reason, array $options, int $operatorId): Order
    {
        throw new RuntimeException('OrderCloseRefundService::closeOrder 待实现(运营端05 §4 关闭订单)');
    }

    /**
     * 发起退款(§5)。
     * 计算可退金额(扣撤单费用),判断 is_settlement_happened:
     *   未分账 → 调 simpleRefund;已分账 → 调 createRefundWorkflow。
     * 生成 refund_application(§5.2)。退款金额不得超过可退金额。需 2FA(§9)。
     * TODO(业务团队):可退金额校验、生成退款单、按分账与否分流。
     */
    public function initiateRefund(Order $order, array $refundData, int $operatorId): array
    {
        throw new RuntimeException('OrderCloseRefundService::initiateRefund 待实现(运营端05 §5 发起退款)');
    }

    /**
     * 简单退款(§5.4,未分账)。平台对公账户原路退回客户。
     * 走审核(财务/主管)→ 调退款通道 → 回调更新退款单/账单/订单;失败进退款异常队列。
     * TODO(业务团队):审核、退款通道调用、回调处理、异常入队。
     */
    public function simpleRefund(int $refundId): array
    {
        throw new RuntimeException('OrderCloseRefundService::simpleRefund 待实现(运营端05 §5.4 简单退款)');
    }

    /**
     * 创建退款工单(§5.5,已分账)。⚠️ 高风险资金流程,合规敏感。
     * 计算各方应退/应补:客户应退A、门店应退还平台B、平台内部清算C(若 funding_source != platform_self);
     * 时序:通知门店线下转账B → 确认到账 → 平台退客户A → 付资方C → 内部资金来源台账+额度还原 → 工单 completed。
     * ⚠️ 不再原路退客户(钱已穿透出去,需从门店追回);内部清算/资方台账属后台,不在 C 端暴露。
     * 各步骤均需财务主管权限(部分 2FA,§9)。
     * TODO(业务团队):生成 refund_workflow、按 §5.5 时序推进、对接财务07资金穿透、还原资方台账。
     */
    public function createRefundWorkflow(int $refundId): array
    {
        throw new RuntimeException('OrderCloseRefundService::createRefundWorkflow 待实现(运营端05 §5.5 退款工单,已分账;高风险资金、资方清算后台不暴露C端)');
    }

    /**
     * 冲正(§6)。⚠️ 只生成反向流水(refund_deduction),不删原流水。财务主管 2FA。
     * 内部资金来源台账/授信按 refunded_amount 还原(资方部分依赖财务07/资方管理,骨架不实现)。
     * TODO(业务团队):生成反向流水、按文档处理保证金/渠道佣金/资方应收,不删原流水。
     */
    public function reverse(Order $order, array $reverseData, int $operatorId): array
    {
        throw new RuntimeException('OrderCloseRefundService::reverse 待实现(运营端05 §6 冲正;只反向流水不删原流水)');
    }

    /**
     * 申请提前归还(§8.1.1)。生成 early_return_request,展示预结算(已使用时间/已发生费用/预计检测费等)。
     * ⚠️ 提前归还不得默认要求客户结清全部未到期费用(§8.1.1,合规边界,同逾期§9.4);
     *    实际应收以已使用时间+已发生费用+检测结果+协议约定实际损失为准。
     * TODO(业务团队):生成申请、预结算快照(仅提示不作最终扣款)、收货检测后生成最终结算。
     */
    public function requestEarlyReturn(Order $order, string $returnMethod, int $customerId): array
    {
        throw new RuntimeException('OrderCloseRefundService::requestEarlyReturn 待实现(运营端05 §8.1.1 提前归还;不默认结清全部未到期费用)');
    }

    /**
     * 归还检测报告 + 费用明细(§8.1.3)。费用"以实际发生 + 凭证为准"。
     * 每个 return_fee_item 计入最终结算前须 is_actual_incurred=true 且至少挂 1 条对应类型凭证(other 除外需文字说明);
     * 电动车定位取回协作费固定项(默认200元/次可配)仍须挂取回凭证证明真实发生。
     * 检测结果客户确认或超3工作日未异议视为认可;提异议进争议处理不直接扣款。
     * TODO(业务团队):落检测报告、费用逐项挂凭证校验、客户确认/异议流程。
     */
    public function recordInspectionAndFees(Order $order, array $report, array $feeItems, int $inspectorId): array
    {
        throw new RuntimeException('OrderCloseRefundService::recordInspectionAndFees 待实现(运营端05 §8.1.3 检测报告与费用凭证;费用以实际发生+凭证为准)');
    }
}
