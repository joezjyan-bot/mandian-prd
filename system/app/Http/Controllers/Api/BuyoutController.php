<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Finance\BuyoutPriceCalculator;
use Illuminate\Http\JsonResponse;

/**
 * 申请购买：试算 + 发起。
 * 【合规敏感】购买价 A 口径待法务确认，口径可切换。
 */
class BuyoutController extends Controller
{
    public function __construct(private BuyoutPriceCalculator $calculator) {}

    /**
     * 试算购买价（独立确认页用）。
     */
    public function quote(Order $order): JsonResponse
    {
        $order->load('bills');
        $result = $this->calculator->calculate($order);

        return response()->json([
            'buyout_price_yuan' => $result['buyout_price_cents'] / 100,
            'deposit_offset_yuan' => $result['deposit_offset_cents'] / 100,
            'customer_pay_yuan' => $result['customer_pay_cents'] / 100,
            // 注：不向 C 端暴露 formula 口径名
        ]);
    }

    /**
     * 发起申请购买（客户主动，独立确认页后调用）。
     * 这里只生成购买账单示范；支付走同一套支付 + 记账流程。
     */
    public function apply(Order $order): JsonResponse
    {
        $order->load('bills');
        $result = $this->calculator->calculate($order);

        // TODO[团队]：生成 buyout 账单、走支付、付清后转移所有权。
        return response()->json([
            'message' => '购买申请已受理（示范）',
            'customer_pay_yuan' => $result['customer_pay_cents'] / 100,
        ]);
    }
}
