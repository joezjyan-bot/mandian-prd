<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 支付流水表（支付流水账）。
 * callback_event_id 唯一约束示范幂等。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_payment_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->string('channel', 32)->default('mock')->comment('支付通道');
            $table->string('channel_trade_no', 64)->nullable()->comment('通道交易号');
            $table->bigInteger('amount_cents')->comment('金额（分）');
            $table->bigInteger('fee_cents')->default(0)->comment('手续费（分）');
            $table->string('status', 24)->default('pending')->comment('pending/success/failed/refunded');
            $table->string('callback_event_id', 64)->nullable()->comment('回调事件号（幂等键）');
            $table->timestamp('paid_time')->nullable();
            $table->timestamps();

            // 幂等：同一回调事件只入账一次
            $table->unique(['channel_trade_no', 'callback_event_id'], 'uniq_trade_event');
            $table->index('order_id');
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_payment_flows');
    }
};
