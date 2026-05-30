<?php

namespace App\Services\External\Real;

use App\Contracts\PaymentContract;
use RuntimeException;

/**
 * 【生产模式·空壳】真实支付对接。
 * TODO[团队]：接入真实支付通道；注意回调幂等（channel_trade_no + callback_event_id + amount）。
 */
class RealPaymentService implements PaymentContract
{
    public function pay(array $payload): array
    {
        throw new RuntimeException('RealPaymentService未实现：TODO[团队]接入真实支付通道。演示阶段请用 EXTERNAL_MODE=mock。');
    }

    public function refund(string $channelTradeNo, int $amountCents, string $reason = ''): array
    {
        throw new RuntimeException('RealPaymentService::refund 未实现。TODO[团队]');
    }
}
