<?php

namespace App\Services\External\Mock;

use App\Services\External\Contracts\PaymentServiceInterface;
use Illuminate\Support\Str;

/**
 * 模拟支付:返回一个"假支付页",演示时点一下即视为支付成功。
 * 回调校验恒真;parseCallback 把演示回调转成统一结构。
 */
class MockPaymentService implements PaymentServiceInterface
{
    public function createPayment(array $payload): array
    {
        $flowId = 'MOCK-PAY-' . Str::upper(Str::random(12));

        return [
            'success'     => true,
            'pay_flow_id' => $flowId,
            'pay_url'     => url("/mock/pay/{$flowId}?amount={$payload['amount_cents']}"),
            'raw'         => ['mock' => true, 'payload' => $payload],
        ];
    }

    public function verifyCallback(array $callback): bool
    {
        // 模拟模式恒真。真实模式必须校验通道签名。
        return true;
    }

    public function parseCallback(array $callback): array
    {
        return [
            'pay_flow_id'      => $callback['pay_flow_id'] ?? '',
            'channel_trade_no' => $callback['channel_trade_no'] ?? ('MOCK-TXN-' . Str::random(10)),
            'amount_cents'     => (int) ($callback['amount_cents'] ?? 0),
            'paid'             => (bool) ($callback['paid'] ?? true),
            'event_id'         => $callback['event_id'] ?? ('EVT-' . Str::random(16)),
        ];
    }
}
