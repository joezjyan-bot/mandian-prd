<?php

namespace App\Services\External\Mock;

use App\Contracts\PaymentContract;
use Illuminate\Support\Str;

/**
 * 【演示模式】支付——直接返回支付成功。
 */
class MockPaymentService implements PaymentContract
{
    public function pay(array $payload): array
    {
        return [
            'payment_id' => 'MOCK-PAY-' . Str::upper(Str::random(10)),
            'channel_trade_no' => 'MOCK-TRADE-' . Str::upper(Str::random(12)),
            'status' => 'success',
            'paid_at' => now()->toIso8601String(),
        ];
    }

    public function refund(string $channelTradeNo, int $amountCents, string $reason = ''): array
    {
        return [
            'refund_no' => 'MOCK-REFUND-' . Str::upper(Str::random(10)),
            'channel_trade_no' => $channelTradeNo,
            'amount_cents' => $amountCents,
            'status' => 'success',
        ];
    }
}
