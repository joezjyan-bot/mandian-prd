<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;

/**
 * 订单审核服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/04_待审核与资方分配.md(§8 审核流程、§6 审核队列、
 *           §11 审核结果、§12 权限日志);全局/02 状态字典 §6.1(审核子状态)、§11.1。
 *
 * ⚠️ 本文件只定义"审核环节有哪些动作、对应文档哪一步、该置什么状态、该校验什么",
 *    方法体均为占位(throw 未实现),不预先写死实现。业务团队按下方注释填充实现即可。
 *
 * —— 设计约定(便于接手,降低耦合)——
 * 1. 本服务只负责"审核状态流转 + 审核结论记录",不直接调用 OrderService / BillPlanService。
 *    审核通过后"生成账单计划"(办单助手02 §8)由上层 Controller 编排:
 *       approve() 成功 → Controller 再调 OrderService::generateBillPlan()
 *    这样审核与账单生成不互相引用,后续改触发时机只动 Controller。
 * 2. 主状态流转必须经 OrderStatus::canTransition 校验(§5.1):
 *       PENDING_REVIEW → PENDING_SIGN(通过) / REVIEW_REJECTED(驳回) / PENDING_SUPPLEMENT(补资料)
 * 3. 审核子状态建议用独立字段 review_sub_status(§11.1:商家订单不单设主状态,
 *    用子状态区分商家自审/平台审/复审)。字段是否落库、落在 orders 还是独立审核表,
 *    由团队决定;本骨架不擅自建表/加字段。
 * 4. 三类订单审核主体(§2):
 *       商家订单  —— 商家/门店自审;平台仅异常介入,且【只能驳回,不可改价】
 *       联营订单  —— 运营端审核(资料 → 风控 → 资金来源分配[§9])
 *       平台订单  —— 运营端审核(资料 → 风控 → 资金来源分配 → 分配门店)
 * 5. 【合规 / C 端红线】资金来源、资方、风控结论、合同模板等字段绝不进 C 端;
 *    资金来源分配(§9)、风控评分算法(§9.3.1)依赖"资方管理/01、03"文档,
 *    属业务团队 + 风控/财务实现,本骨架不涉及,仅在流程中预留调用位。
 */
class ReviewService
{
    /**
     * 接单:把订单标记给某审核客服处理(§8「接单」、§6「待接单→待资料审核」)。
     * 文档:§8 "接单(系统自动标记做单客服 XXX)";§12 权限=审核客服,记操作日志。
     * 子状态:REVIEW_WAITING → REVIEW_PROCESSING(§6.1)。
     * 入参:$order(须 PENDING_REVIEW);$reviewerId 审核客服 ID。
     * 出参:Order。
     * TODO(业务团队):标记做单客服、置 review_sub_status=REVIEW_PROCESSING、写接单日志。
     */
    public function claim(Order $order, int $reviewerId): Order
    {
        throw new RuntimeException('ReviewService::claim 待实现(运营端04 §8 接单)');
    }

    /**
     * 阶段1 资料审核通过(§8 阶段1)。
     * 商家订单:商家/门店自审通过后即可进入下一步;
     * 联营/平台订单:资料通过 → 进入风控(passRiskControl)。
     * TODO(业务团队):校验资料完整性(§7.2 资料状态),记审核意见,推进到风控/签约前置。
     */
    public function approveDataStage(Order $order): Order
    {
        throw new RuntimeException('ReviewService::approveDataStage 待实现(运营端04 §8 阶段1 资料审核)');
    }

    /**
     * 阶段2 风控审批通过(§8 阶段2,仅联营/平台订单必经)。
     * ⚠️ 本方法只接收"风控已通过"的结论并推进流程;
     *    风控评分、黑名单判断、授信判断等核心风控逻辑依赖「资方管理/03」,
     *    由风控系统/业务团队实现,本骨架不编写任何风控判定规则。
     * TODO(业务团队):对接风控接口/报告,通过后方可进入资金来源分配(§9)。
     */
    public function passRiskControl(Order $order): Order
    {
        throw new RuntimeException('ReviewService::passRiskControl 待实现(运营端04 §8 阶段2 风控,依赖 资方管理/03)');
    }

    /**
     * 审核通过 → 订单进入签约(§8 阶段5「确认订单进入支付」前置;§11「确认订单进入支付」)。
     * 主状态:PENDING_REVIEW → PENDING_SIGN(经 OrderStatus::canTransition 校验)。
     * 子状态:REVIEW_APPROVED。记 reviewed_at、审核意见(§11 必填)。
     *
     * 关键约束(§8 关键约束,实现时必须校验):
     *   - 联营/平台订单未通过风控,不可通过;
     *   - 联营/平台订单未分配资金来源(§9),不可通过;
     *   - 平台订单未分配履约门店(07 文档),不可通过;
     *   - 客户未在 IM 确认,不可通过。
     *
     * ⚠️ 通过后"生成账单计划"由 Controller 编排调用 OrderService::generateBillPlan(),
     *    本方法不直接调账单服务(见类注释设计约定 1)。
     * TODO(业务团队):落实上述前置校验、状态流转、结论记录。
     */
    public function approve(Order $order, string $remark): Order
    {
        throw new RuntimeException('ReviewService::approve 待实现(运营端04 §8/§11 审核通过 → PENDING_SIGN)');
    }

    /**
     * 审核驳回(§11「审核不通过」;商家订单异常介入也走此处,§2「只能驳回」)。
     * 主状态:PENDING_REVIEW → REVIEW_REJECTED。子状态:REVIEW_REJECTED。
     * 入参:$reason 驳回原因(§11 必填:拒绝原因、通知对象)。
     * TODO(业务团队):状态流转、记驳回原因与通知对象、触发通知。
     */
    public function reject(Order $order, string $reason): Order
    {
        throw new RuntimeException('ReviewService::reject 待实现(运营端04 §11 审核不通过)');
    }

    /**
     * 要求补资料(§11「要求补资料」)。
     * 主状态:PENDING_REVIEW → PENDING_SUPPLEMENT。子状态:REVIEW_SUPPLEMENT。
     * 入参:$items 补充资料项;$deadline 截止时间(§11 必填)。
     * TODO(业务团队):状态流转、生成客户/商家补资料待办、记录补充项与时效。
     */
    public function requireSupplement(Order $order, array $items, ?\DateTimeInterface $deadline = null): Order
    {
        throw new RuntimeException('ReviewService::requireSupplement 待实现(运营端04 §11 要求补资料)');
    }

    /**
     * 转主管复核(§11「转主管复核」;§6 异常队列)。
     * 子状态:REVIEW_ESCALATED。主状态保持 PENDING_REVIEW(进异常队列,不单设主状态,§11.1)。
     * 入参:$reason 复核原因。
     * TODO(业务团队):标记复核、进异常队列、记日志。
     */
    public function escalate(Order $order, string $reason): Order
    {
        throw new RuntimeException('ReviewService::escalate 待实现(运营端04 §11 转主管复核)');
    }
}
