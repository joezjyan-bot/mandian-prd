<?php

namespace App\Services\External\Contracts;

/**
 * 支付接口。业务层只依赖本接口,不关心走模拟还是真实通道。
 * 所有金额单位:分(整数)。
 */
interface PaymentServiceInterface
{
    /**
     * 发起支付(下单首期/账单/购买款)。
     *
     * @param  array  $payload  ['bill_id'=>int,'order_id'=>int,'amount_cents'=>int,'subject'=>string]
     * @return array  ['success'=>bool,'pay_flow_id'=>string,'pay_url'=>string,'raw'=>array]
     */
    public function createPayment(array $payload): array;

    /**
     * 校验支付回调签名(真实模式必须实现;模拟模式恒真)。
     */
    public function verifyCallback(array $callback): bool;

    /**
     * 解析回调为统一结构。
     * @return array ['pay_flow_id'=>string,'channel_trade_no'=>string,'amount_cents'=>int,'paid'=>bool,'event_id'=>string]
     */
    public function parseCallback(array $callback): array;
}
