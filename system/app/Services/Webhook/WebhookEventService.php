<?php

namespace App\Services\Webhook;

use RuntimeException;

/**
 * 第三方回调统一处理服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:全局/07_订单接口事件与补偿动作.md(§1 事件处理原则、§2-§6 各类事件、
 *           §7 异常队列、§8 人工补偿边界、§9 验收标准)。
 *
 * ⚠️ 方法体均为占位(throw 未实现),不预先写死实现。业务团队按注释填充即可。
 *
 * —— 通用回调处理契约(§1,所有回调必须遵守)——
 * 1. 先入库,再验签,再处理业务(§1.2):回调到达先落 webhook_event 原始记录,验签通过才进业务。
 * 2. 幂等(§1.3):同一回调重复到达不得重复入账/签约/分账。幂等键建议用通道+事件号
 *    (参考现有 FinancePostingService 用 channel_trade_no + callback_event_id 去重的做法)。
 * 3. 失败进异常队列(§1.4 / §7),可自动重试 + 人工重放。
 * 4. 回调不得绕过权限直接改高风险状态(§1.6);高风险补偿需审批(§8)。
 * 5. 人工补偿必须写操作日志并保留原始异常(§1.5)。
 *
 * —— 设计约定(便于接手,降低耦合)——
 * - 本服务只做"回调的统一收口 + 分发",具体业务处理委托给已有/待建的领域服务:
 *     支付成功     → FinancePostingService::postPaymentSuccess(已实现,已幂等)
 *     监管锁回调   → DeviceLockContract / DeliveryService::verifyLock(已实现)
 *     合同签署回调 → ContractService::onCustomerSigned/onStoreSigned/onAllSigned(骨架)
 *     公证完成回调 → ContractService::initiateNotary 后续(骨架)
 *     物流签收回调 → DeliveryService::confirmReceipt(已实现)
 *   分发只调领域服务,不在本服务里写业务细节,避免回调层与业务层耦合。
 * - 现有 PaymentCallbackController + FinancePostingService 已实现支付回调幂等;
 *   接入本统一收口时,让支付回调也先经 record()/verify()/dedupe() 再调 FinancePostingService,
 *   不改 FinancePostingService 既有逻辑。
 * - webhook_event 表、异常队列表(§7 字段)是否落库、表结构由团队决定;骨架不擅自建表。
 */
class WebhookEventService
{
    /**
     * 第1步:回调入库(§1.2 先入库)。
     * 落 webhook_event 原始记录:通道、事件类型、业务对象、原始报文、到达时间。
     * 入参:$channel 通道(alipay/wechat/zhongtai/esign/...);$eventType 事件类型;$rawPayload 原始报文。
     * 出参:webhook_event 记录(含 id),供后续步骤引用。
     * TODO(业务团队):落库原始记录,返回记录标识。
     */
    public function record(string $channel, string $eventType, array $rawPayload): array
    {
        throw new RuntimeException('WebhookEventService::record 待实现(全局07 §1.2 先入库)');
    }

    /**
     * 第2步:验签(§1.2 再验签)。
     * 按通道规则验签;验签失败 → 进异常队列(§7「回调验签失败」),不进业务。
     * TODO(业务团队):按通道实现验签;失败调 pushToExceptionQueue。
     */
    public function verify(string $channel, array $rawPayload): bool
    {
        throw new RuntimeException('WebhookEventService::verify 待实现(全局07 §1.2 验签)');
    }

    /**
     * 第3步:幂等检查(§1.3)。
     * 同一回调(通道+事件号)已成功处理过 → 返回 false(已处理,跳过),不得重复处理。
     * 参考 FinancePostingService 既有去重口径(channel_trade_no + callback_event_id)。
     * TODO(业务团队):按幂等键判重。
     */
    public function isDuplicate(string $channel, string $eventId): bool
    {
        throw new RuntimeException('WebhookEventService::isDuplicate 待实现(全局07 §1.3 幂等)');
    }

    /**
     * 第4步:分发到领域服务(§2-§6)。
     * 验签通过 + 非重复后,按 eventType 路由到对应领域服务处理:
     *   payment_success → FinancePostingService::postPaymentSuccess
     *   lock_callback   → DeliveryService::verifyLock(签收后监管锁校验)
     *   contract_signed → ContractService::onAllSigned / onCustomerSigned / onStoreSigned
     *   notary_done     → 公证完成处理
     *   logistics_signed→ DeliveryService::confirmReceipt
     * ⚠️ 本方法只路由,不写业务细节;高风险状态变更经领域服务的状态校验(§1.6)。
     * 处理失败 → pushToExceptionQueue(§1.4)。
     * TODO(业务团队):实现 eventType→领域服务的路由分发与失败兜底。
     */
    public function dispatch(string $eventType, array $payload): array
    {
        throw new RuntimeException('WebhookEventService::dispatch 待实现(全局07 §2-§6 事件分发)');
    }

    /**
     * 失败进异常队列(§7)。
     * 写异常队列字段:事件编号/类型/业务对象/通道/请求摘要/回调摘要/当前状态/
     * 失败原因/重试次数/下次重试时间/是否允许人工重放/处理人/处理结果。
     * TODO(业务团队):落异常队列,支持自动重试调度。
     */
    public function pushToExceptionQueue(array $event, string $reason): void
    {
        throw new RuntimeException('WebhookEventService::pushToExceptionQueue 待实现(全局07 §7 异常队列)');
    }

    /**
     * 人工重放(§8 允许的人工补偿:重放回调)。
     * 仅重放回调本身;人工改支付成功/分账成功/绕过实名/删日志等(§8 不允许)一律拒绝。
     * 必须写操作日志 + 保留原始异常(§1.5)。
     * TODO(业务团队):校验补偿边界(§8)、重放、记操作日志。
     */
    public function replay(int $exceptionEventId, int $operatorId): array
    {
        throw new RuntimeException('WebhookEventService::replay 待实现(全局07 §8 人工重放,守补偿边界)');
    }
}
