<?php

namespace App\Services\Refund;

use App\Models\Order;
use RuntimeException;

/**
 * 订单撤单 + 退款工单推进 + 补充合同服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/10_订单撤单与补充合同.md(§1 撤单、§2 退款资金链路、§3 退款工单、
 *           §4 补充合同、§6 权限);对接 财务管理/07 §6、运营端05(关闭退款)。
 *
 * ⚠️ 方法体均为占位(throw 未实现),不预先写死实现。
 *
 * —— 与 OrderCloseRefundService(模块J)的分工(避免重叠/耦合混乱)——
 * - OrderCloseRefundService:关闭订单、发起退款、简单退款、生成退款工单、冲正、提前归还、归还检测。
 * - 本服务(CancelAndSupplementService):撤单申请受理(C端取消/客服审核)、退款工单的【逐步推进】
 *   (确认门店转账→退客户→退资方→完成)、补充合同全流程。
 * - 退款工单的"生成"在 OrderCloseRefundService::createRefundWorkflow;本服务负责生成之后的状态推进。
 *   两者通过 refund_workflow 记录衔接,不互相内部调用,由 Controller/财务流程编排。
 *
 * —— 撤单关键规则(§1/§2)——
 * 1. 可撤单阶段(§1.1):审核中/待付款未付款客户可直接撤;已付款/已签约需客服同意;
 *    待发货起需主管;履约中客户不可撤(走归还);已留购完成不可撤(§7)。
 * 2. 撤单费用(§2.6/§2.7):待签约阶段订单总价×1.5%;履约中按合同账单总租金×30%;【平台收】。
 *    退款金额 = MAX(0, 客户已付 − 撤单费用 − 不可退费用)。
 * 3. 退款路径(§2.1):未分账→平台对公原路退;已分账→强制退款工单(门店线下转账追回→退客户→退资方)。
 * 4. is_settlement_happened 判断(§2.2):order_settlement 或 monthly_split 已完成,或资方应收已变动。
 *
 * —— 退款工单推进(§3.2 状态流转)——
 * pending → merchant_paid(门店转账到位,财务确认) → refunded_customer(退客户) →
 * refunded_funder(退资方,若 funding_source != platform_self) → completed(账面核对闭环)。
 * 权限(§3.6/§6):确认门店转账/退客户=财务客服+;触发内部清算/完成工单=财务主管;取消工单=运营主管。
 * ⚠️ 资方清算属后台,不在 C 端暴露;内部资金来源台账/额度按 refunded_amount 还原(资方逻辑依赖财务07/资方管理,骨架不实现)。
 *
 * —— 补充合同关键规则(§4)——
 * 1. 适用:设备编码改错/客户姓名/商品规格/资方变更/履约门店变更(§4.2);【严禁改金额/月供/留购价】(§4.2)。
 * 2. 状态机(§4.4):SUPP_NONE/PENDING_REVIEW/PENDING_SIGN/SIGNING/SIGNED/REJECTED。
 * 3. 与主合同同等效力,冲突以补充合同为准;一单可多份(supp_seq_no 递增),永久保留(§4.7)。
 * 4. 走 e签宝客户签署(§4.5),回调后更新订单对应字段(如设备编码改新值)。
 *
 * —— 设计约定(便于接手)——
 * - order_cancel_request / refund_workflow / supplement_contract 等表(§2.10/§3.1/§4.8)由团队按文档建,骨架不建表。
 * - 补充合同电子签走 EsignContract;本服务只定义契约,不实现签署通道细节。
 */
class CancelAndSupplementService
{
    /**
     * 受理撤单申请(§1.2)。
     * C 端取消(审核中/未付款直接撤)或进入撤单申请队列(已付款/已签约需客服审核)。
     * 生成 order_cancel_request,记录撤单时订单状态、is_settlement_happened、撤单原因。
     * TODO(业务团队):按阶段判断可否直接撤、生成撤单申请、置队列。
     */
    public function requestCancel(Order $order, string $requestedByType, int $requesterId, string $reason): array
    {
        throw new RuntimeException('CancelAndSupplementService::requestCancel 待实现(运营端10 §1.2 撤单申请)');
    }

    /**
     * 客服审核撤单(§1.2.2)。同意→按 §2 判断分账与否(未分账原路退/已分账生成退款工单);拒绝→通知客户。
     * 计算撤单费用(§2.6/§2.7:1.5% 或 30%,平台收)与退款金额。分账已发生需 2FA(§6)。
     * ⚠️ 已分账场景:本方法只到"生成退款工单"为止,工单推进走下方 advanceRefundWorkflow。
     * TODO(业务团队):审核、算撤单费用与退款额、按分账与否分流(已分账调 OrderCloseRefundService::createRefundWorkflow)。
     */
    public function approveCancel(Order $order, int $cancelRequestId, int $operatorId): array
    {
        throw new RuntimeException('CancelAndSupplementService::approveCancel 待实现(运营端10 §1.2.2 撤单审核;撤单费用平台收)');
    }

    /**
     * 推进退款工单(§3.2 状态流转)。
     * action ∈ {confirm_merchant_paid, refund_customer, refund_funder, complete, cancel_workflow}。
     * 按 §3.6 权限校验:确认门店转账/退客户=财务客服+;退资方/完成=财务主管;取消=运营主管。
     * 退资方时同步还原内部资金来源台账/额度(资方逻辑依赖财务07,骨架不实现,留调用位)。
     * ⚠️ 已分账退款不原路退;客户退款等门店转账到位后再出款(§2.4 时序)。
     * TODO(业务团队):按 action 推进工单状态、权限校验、对接财务资金穿透、写工单时间线日志。
     */
    public function advanceRefundWorkflow(int $workflowId, string $action, int $operatorId): array
    {
        throw new RuntimeException('CancelAndSupplementService::advanceRefundWorkflow 待实现(运营端10 §3.2 退款工单推进)');
    }

    /**
     * 发起补充合同(§4.5)。
     * 变更类型:device_code/customer_name/product_spec/funder/fulfillment_store/other(§4.8);
     * ⚠️【严禁改金额/月供/留购价】(§4.2)——实现时必须拒绝金额类变更。
     * 记录旧值快照 + 新值 + 原因 + 证明材料;进 SUPP_PENDING_REVIEW。
     * TODO(业务团队):校验变更类型(拒金额变更)、按权限发起(§4.3)、生成补充合同记录。
     */
    public function initiateSupplementContract(Order $order, string $variationType, $oldValue, $newValue, string $reason, int $operatorId): array
    {
        throw new RuntimeException('CancelAndSupplementService::initiateSupplementContract 待实现(运营端10 §4.5 补充合同;严禁改金额)');
    }

    /**
     * 审核补充合同(§4.5 第4步)。通过→生成 PDF(按订单当前合同模板)→ SUPP_PENDING_SIGN;驳回→ SUPP_REJECTED。
     * 权限(§4.3/§6):设备编码客服/主管;资方/门店/规格变更需主管。
     * TODO(业务团队):审核、生成补充合同 PDF、状态流转、通知。
     */
    public function reviewSupplementContract(int $suppContractId, bool $approved, string $remark, int $reviewerId): array
    {
        throw new RuntimeException('CancelAndSupplementService::reviewSupplementContract 待实现(运营端10 §4.5 补充合同审核)');
    }

    /**
     * 补充合同签署回调(§4.5 第7步)。
     * 签署成功→ SUPP_SIGNED + 更新订单对应字段(如设备编码改新值);拒签→ SUPP_REJECTED。
     * ⚠️ 与主合同同等效力,冲突以补充合同为准;永久保留(§4.7)。
     * TODO(业务团队):处理 e签宝回调、更新订单字段、状态流转、留档。
     */
    public function onSupplementSigned(int $suppContractId, array $callback): array
    {
        throw new RuntimeException('CancelAndSupplementService::onSupplementSigned 待实现(运营端10 §4.5 补充合同签署回调)');
    }
}
