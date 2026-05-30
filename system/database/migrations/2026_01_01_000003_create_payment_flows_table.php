<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 支付流水账(四账之二)。通道支付/回调/退款/手续费/幂等。
 * 本表 ≠ 钱包余额。金额单位:分。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_flows', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bill_id')->nullable()->index();
            $t->unsignedBigInteger('order_id')->index();
            $t->string('channel', 20)->default('mock')->comment('mock/tonglian/wechat/alipay');
            $t->string('pay_flow_id', 64)->index()->comment('内部支付单号');
            $t->string('channel_trade_no', 64)->nullable()->comment('通道交易号');
            $t->bigInteger('amount_cents');
            $t->bigInteger('fee_cents')->default(0);
            $t->string('status', 20)->default('pending')->comment('pending/success/failed/refunded');
            $t->string('callback_event_id', 64)->nullable()->unique()->comment('回调事件ID,幂等键');
            $t->timestamp('paid_time')->nullable();
            $t->json('raw')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_flows');
    }
};
