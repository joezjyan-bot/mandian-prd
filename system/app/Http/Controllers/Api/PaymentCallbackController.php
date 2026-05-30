<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancePostingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 支付回调（幂等）。真实通道回调也走这里。
 */
class PaymentCallbackController extends Controller
{
    public function __construct(private FinancePostingService $finance) {}

    public function handle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|integer',
            'bill_id' => 'nullable|integer',
            'merchant_id' => 'required|integer',
            'amount_cents' => 'required|integer|min:0',
            'channel_trade_no' => 'required|string',
            'callback_event_id' => 'required|string',
        ]);

        $posted = $this->finance->postPaymentSuccess($data);

        // 重复回调返回 ok 但 posted=false，不重复入账
        return response()->json(['ok' => true, 'posted' => $posted]);
    }
}
