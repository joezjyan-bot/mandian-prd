<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalculatorConfig;
use App\Models\PriceSnapshot;
use App\Services\Calculator\CalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 办单助手：试算 + 生成报价快照（下单二维码）。
 */
class CalculatorController extends Controller
{
    public function __construct(private CalculatorService $calculator) {}

    /** 试算（不落库） */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'config_id' => 'required|integer',
            'device_value_cents' => 'required|integer|min:0',
            'periods' => 'required|integer|min:1',
            'down_payment_ratio' => 'nullable|integer|min:0|max:100',
            'deposit_cents' => 'nullable|integer|min:0',
            'selected_services' => 'nullable|array',
        ]);
        $config = CalculatorConfig::findOrFail($data['config_id']);
        $quote = $this->calculator->quote($config, $data);
        return response()->json($quote);
    }

    /** 生成报价快照 + 二维码信息 */
    public function snapshot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'config_id' => 'required|integer',
            'device_value_cents' => 'required|integer|min:0',
            'periods' => 'required|integer|min:1',
            'down_payment_ratio' => 'nullable|integer|min:0|max:100',
            'deposit_cents' => 'nullable|integer|min:0',
            'selected_services' => 'nullable|array',
            'store_id' => 'nullable|integer',
        ]);
        $config = CalculatorConfig::findOrFail($data['config_id']);
        $quote = $this->calculator->quote($config, $data);
        $snapshot = $this->calculator->makeSnapshot($config, $data, $quote);

        return response()->json([
            'snapshot_no' => $snapshot->snapshot_no,
            'order_qr_url' => url('/c/order?snap=' . $snapshot->snapshot_no), // 客户扫码入口（示范）
            'quote' => $quote,
        ], 201);
    }
}
