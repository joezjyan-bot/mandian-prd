<?php

namespace App\Services\External\Real;

use App\Services\External\Contracts\PaymentServiceInterface;

/**
 * 真实支付对接(如通联 / 微信 / 支付宝)。
 * TODO[团队]: 实现下单、回调验签、回调解析。注意金额单位为分。
 */
class RealPaymentService implements PaymentServiceInterface
{
    public function createPayment(array $payload): array
    {
        // TODO[团队]: 调用支付通道统一下单接口,返回 pay_flow_id 与 pay_url(或预支付参数)。
        throw new \RuntimeException('RealPaymentService::createPayment 待团队对接');
    }

    public function verifyCallback(array $callback): bool
    {
        // TODO[团队]: 必须严格校验通道回调签名,防伪造。返回 false 则拒绝处理。
        throw new \RuntimeException('RealPaymentService::verifyCallback 待团队对接');
    }

    public function parseCallback(array $callback): array
    {
        // TODO[团队]: 把通道回调字段映射为统一结构。
        //  必须给出稳定的 event_id 用于幂等(避免重复入账)。
        throw new \RuntimeException('RealPaymentService::parseCallback 待团队对接');
    }
}
