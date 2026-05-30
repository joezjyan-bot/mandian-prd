<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;

/**
 * 改价 / 改套餐 / 补资料服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/06_改价补资料与客服IM.md(§2 核心口径、§3 改套餐、§4 补资料、§7 日志、§8 风控);
 *           价格快照机制对齐 办单助手02 §9(下单后待审核前可改,重算快照留痕)。
 *
 * ⚠️ 方法体均为占位(throw 未实现),不预先写死实现。
 *
 * —— 核心口径(§2 / §8,务必遵守)——
 * 1. 改套餐/改价【不能直接覆盖原始办单助手价格】,必须生成新价格快照;原快照不可删(§2.1、§3 规则2)。
 *    可复用 CalculatorService 重算 → makeSnapshot 生成新快照(同源,保证账单/合同变量一致)。
 * 2. 改价后客户下单页/订单详情/账单/合同变量都用最新生效快照(§2.2、§8.2)。
 * 3. 改价/补资料【不能绕过订单审核状态机】(§8.1);金额变化超阈值需主管复核(§3 规则3)。
 * 4. 合同已签署后改价必须走补充合同或订单重审(§3 规则4);已支付改价需走退款/补款/冲正(§8.3)。
 * 5. 补资料必须生成客户/商家待办,不能只写备注(§2.3、§4)。
 * 6. 客户未确认新方案前不进入后续发货(§3 规则5)。
 *
 * —— 隐私边界(§6,客服 IM)——
 * IM 上下文只带:订单号/类型/商家门店/客户脱敏摘要/商品方案/当前状态/当前待办/最近备注;
 * 【不推送完整身份证、银行卡、详细风控报告】(§6)。C 端红线照旧(资方/分账/服务费拆分不暴露)。
 *
 * —— 设计约定(便于接手)——
 * - 价格快照走已实现的 PriceSnapshot + CalculatorService;改价只新增快照版本,不动历史快照。
 * - 待办、IM 会话、附件中心由对应模块承载,本服务只定义改价/补资料的契约与编排点,不耦合 IM 实现。
 * - 改价后"账单/合同重读快照"由 Controller 编排(改价 → 重算快照 → 审核通过后 generateBillPlan),不在此直接改账单。
 */
class OrderAdjustService
{
    /**
     * 改套餐 / 改价(§3)。生成新价格快照(不覆盖原始),记录原/新方案 + 原因 + 是否需客户确认。
     * 阶段限制:下单后待审核前可改(办单助手02 §9);合同已签走补充合同/重审(§3 规则4);
     * 已支付走退款/补款/冲正(§8.3)。金额变化超阈值需主管复核(§3 规则3)。
     * TODO(业务团队):用 CalculatorService 重算、makeSnapshot 生成新快照、阶段/权限/阈值校验、写改价日志。
     */
    public function adjustPlan(Order $order, array $newPlanInput, string $reason, int $operatorId): array
    {
        throw new RuntimeException('OrderAdjustService::adjustPlan 待实现(运营端06 §3 改套餐/改价;生成新快照不覆盖原始)');
    }

    /**
     * 标记客户已确认新方案(§3 规则5:未确认不进入发货)。
     * TODO(业务团队):置客户确认、解锁后续流程、写日志。
     */
    public function confirmAdjustedPlan(Order $order, int $snapshotId): Order
    {
        throw new RuntimeException('OrderAdjustService::confirmAdjustedPlan 待实现(运营端06 §3 客户确认新方案)');
    }

    /**
     * 发起补资料(§4)。生成客户/商家待办并推送(小程序/短信/站内信/IM),订单进待补资料队列。
     * 默认阻断审核;资料进附件中心保留旧版本;提交后重新进入审核。
     * 对应主状态 PENDING_SUPPLEMENT(与 ReviewService::requireSupplement 一致,二者择一编排,避免重复置位)。
     * TODO(业务团队):生成待办、推送通知、置队列、附件版本管理。
     */
    public function requestSupplementMaterials(Order $order, string $target, array $materialTypes, string $reason, ?\DateTimeInterface $deadline, int $operatorId): array
    {
        throw new RuntimeException('OrderAdjustService::requestSupplementMaterials 待实现(运营端06 §4 补资料)');
    }

    /**
     * 构造客服 IM 订单上下文(§5 / §6)。
     * ⚠️ 只返回:订单号/类型/商家门店/客户脱敏摘要/商品方案/当前状态/当前待办/最近备注;
     *    【绝不含完整身份证、银行卡、详细风控报告】(§6),也不含资方/分账等 C 端红线字段。
     * TODO(业务团队):按 §6 字段表组装脱敏上下文,接 IM 模块。
     */
    public function buildImContext(Order $order): array
    {
        throw new RuntimeException('OrderAdjustService::buildImContext 待实现(运营端06 §6 IM上下文;脱敏、不含身份证/银行卡/风控报告)');
    }
}
