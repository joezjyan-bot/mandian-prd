<?php

namespace App\Contracts;

/**
 * 电子签约接口。
 * 业务代码只依赖本接口，不关心是 mock 还是 real。
 */
interface EsignContract
{
    /**
     * 发起合同签署。
     *
     * @param array $payload 订单/客户/合同快照数据
     * @return array{sign_id:string, status:string, signed_at:?string, contract_url:?string}
     */
    public function sign(array $payload): array;

    /**
     * 查询签署状态。
     */
    public function status(string $signId): array;
}
