<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\External\Contracts\PaymentServiceInterface;
use App\Services\Finance\FinancePostingService;

/**
 * 支付回调入口。模拟模式下,演示页"点击支付成功"即打到这里。
 * 真实模式:必须先 verifyCallback 验签,再处理。幂等由 event_id 保证。
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private PaymentServiceInterface $payment,
        private FinancePostingService $finance,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $callback = $request->all();

        if (! $this->payment->verifyCallback($callback)) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $parsed = $this->payment->parseCallback($callback);
        if (! $parsed['paid']) {
            return response()->json(['ok' => true, 'note' => 'not paid, ignored']);
        }

        $result = $this->finance->onPaymentSuccess($parsed);

        return response()->json(['ok' => true, 'result' => $result]);
    }
}
