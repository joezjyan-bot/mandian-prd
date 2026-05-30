<?php

namespace App\Services\External\Contracts;

/**
 * 电子签接口。业务层只依赖本接口,不关心走模拟还是真实(e签宝/法大大等)。
 */
interface EsignServiceInterface
{
    /**
     * 发起合同签署。
     *
     * @param  array  $payload  ['order_id'=>int,'template_code'=>string,'signer'=>array,'snapshot'=>array]
     * @return array  ['success'=>bool,'sign_flow_id'=>string,'sign_url'=>string,'raw'=>array]
     */
    public function createSignFlow(array $payload): array;

    /**
     * 查询签署状态。
     * @return array ['signed'=>bool,'signed_at'=>?string,'pdf_url'=>?string]
     */
    public function querySignStatus(string $signFlowId): array;
}
