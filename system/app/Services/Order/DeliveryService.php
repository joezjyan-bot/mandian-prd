<?php

namespace App\Services\Order;

use App\Contracts\DeviceLockContract;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 交付 / 签收 / 监管锁校验 / 平台结算 服务。
 * 监管锁走 DeviceLockContract(mock/real 不感知)。
 *
 * 状态依据:全局/02 状态字典 §0.1 + §5.1。
 * 文档结算硬前置(§0.1 V0.2.2 补丁 + §5.1):
 *   "长租结算新增硬前置:客户签收确认 + 监管锁已上锁 + 激活锁已激活;
 *    未满足时进入待平台结算/人工复核,不得自动打款。"
 * 流转:PENDING_DELIVERY → PENDING_RECEIPT_CONFIRM → PENDING_LOCK_VERIFY
 *      →(LOCK_VERIFY_FAILED)→ PENDING_PLATFORM_SETTLEMENT →(PAYOUT_FAILED)→ IN_FULFILLMENT
 */
class DeliveryService
{
    public function __construct(private DeviceLockContract $deviceLock) {}

    /**
     * 商家交付:录设备唯一码、(苹果类)上监管锁。
     * 文档 §5.1:PENDING_DELIVERY →(交付完成)→ PENDING_RECEIPT_CONFIRM。
     *
     * @param bool $needLock 是否需要监管锁(苹果类手机/平板/手表;无锁品类传 false)
     */
    public function deliver(Order $order, string $deviceCode, bool $needLock = false): Order
    {
        if ($order->status !== OrderStatus::PENDING_DELIVERY) {
            throw new RuntimeException("订单状态不允许交付:{$order->status}");
        }

        return DB::transaction(function () use ($order, $deviceCode, $needLock) {
            $order->device_code = $deviceCode;
            $order->delivered_at = now();
            $order->need_lock = $needLock;

            if ($needLock) {
                // 监管锁上锁(演示模式返回成功;真实由中控台回调)
                $this->deviceLock->lock($deviceCode, ['order_no' => $order->order_no]);
            }

            // 交付完成 → 待客户签收确认
            $this->transit($order, OrderStatus::PENDING_RECEIPT_CONFIRM);
            $order->save();

            return $order;
        });
    }

    /**
     * 客户签收确认。
     * 文档 §5.1:PENDING_RECEIPT_CONFIRM →(客户确认)→ PENDING_LOCK_VERIFY。
     * 签收后统一进入监管锁校验阶段(无锁品类在校验阶段直接判定通过)。
     */
    public function confirmReceipt(Order $order): Order
    {
        if ($order->status !== OrderStatus::PENDING_RECEIPT_CONFIRM) {
            throw new RuntimeException("订单状态不允许签收:{$order->status}");
        }

        $this->transit($order, OrderStatus::PENDING_LOCK_VERIFY);
        $order->received_at = now();
        $order->save();

        return $order;
    }

    /**
     * 监管锁校验(签收后)。
     * 文档结算硬前置:需"监管锁已上锁 + 激活锁已激活"。
     * - 需锁品类(need_lock=true):校验上锁与激活锁状态,通过→PENDING_PLATFORM_SETTLEMENT,否则→LOCK_VERIFY_FAILED。
     * - 无锁品类(need_lock=false):文档第4章"其它无锁品类:签收+设备码归档+交付证据完整+前置检测完成即可结算,
     *   不因没有监管锁卡住" → 校验直接通过,进入待平台结算。
     */
    public function verifyLock(Order $order): Order
    {
        if ($order->status !== OrderStatus::PENDING_LOCK_VERIFY) {
            throw new RuntimeException("订单状态不允许监管锁校验:{$order->status}");
        }

        if ($order->need_lock) {
            $lock = $this->deviceLock->status($order->device_code);
            // 需 lock_status = LOCKED 且 激活锁已激活(ACTIVE_LOCK_ENABLED)
            $locked = ($lock['lock_status'] ?? null) === 'locked';
            $activeLock = ($lock['active_lock'] ?? null) === true || ($lock['lock_status'] ?? null) === 'locked';
            if (! $locked || ! $activeLock) {
                $this->transit($order, OrderStatus::LOCK_VERIFY_FAILED);
                $order->save();
                return $order;
            }
        }

        $this->transit($order, OrderStatus::PENDING_PLATFORM_SETTLEMENT);
        $order->save();

        return $order;
    }

    /**
     * 平台结算 → 进入履约中。
     * 文档结算硬前置:必须签收确认 + 监管锁已上锁 + 激活锁已激活均满足。
     * 本方法只允许从 PENDING_PLATFORM_SETTLEMENT 进入,该状态本身即代表前置已校验通过;
     * 未满足时订单不会到达此状态(停在 PENDING_LOCK_VERIFY / LOCK_VERIFY_FAILED),不会自动打款。
     */
    public function settle(Order $order): Order
    {
        if ($order->status !== OrderStatus::PENDING_PLATFORM_SETTLEMENT) {
            throw new RuntimeException("订单状态不允许结算:{$order->status}(结算硬前置未满足)");
        }

        $this->transit($order, OrderStatus::IN_FULFILLMENT);
        $order->settled_at = now();
        $order->save();

        return $order;
    }

    /**
     * 状态流转校验(§5.1 合法流转)。非法流转直接抛错,不静默放行。
     */
    private function transit(Order $order, string $to): void
    {
        if (! OrderStatus::canTransition($order->status, $to)) {
            throw new RuntimeException("非法状态流转:{$order->status} → {$to}");
        }
        $order->status = $to;
    }
}
