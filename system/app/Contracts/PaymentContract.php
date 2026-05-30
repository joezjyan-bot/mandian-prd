<?php

namespace App\Contracts;

/**
 * 支付接口。业务代码只依赖本接口。
 */
interface PaymentContract
{
    /**
     * 发起支付。
     *
     * @param array $payload 含 bill_id, order_id, amount_cents 等
     * @return array{payment_id:string, channel_trade_no:string, status:string, paid_at:?string}
     */
    public function pay(array $payload): array;

    /**
     * 退款。
     */
    public function refund(string $channelTradeNo, int $amountCents, string $reason = ''): array;
}
