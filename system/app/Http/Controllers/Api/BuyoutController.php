<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Services\Finance\BuyoutPriceCalculator;

/**
 * 申请购买:独立确认页。客户主动发起,看清金额后才支付,付清才转移所有权。
 * ⚠️ 购买价 A 口径合规敏感,待法务确认(见 BuyoutPriceCalculator)。
 */
class BuyoutController extends Controller
{
    public function __construct(private BuyoutPriceCalculator $calc) {}

    /** 展示购买价(独立确认页) */
    public function quote(Order $order): JsonResponse
    {
        $q = $this->calc->calculate($order);
        return response()->json([
            'order_no'             => $order->order_no,
            'buyout_price_cents'   => $q['buyout_price_cents'],
            'deposit_offset_cents' => $q['deposit_offset_cents'],
            'customer_pay_cents'   => $q['customer_pay_cents'],
            // formula 仅内部排查用,生产可不返回给 C 端
        ]);
    }
}
