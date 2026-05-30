<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 订单表（订单业务账主表）。
 * 金额一律存“分”（bigint）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单号');
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->unsignedBigInteger('merchant_id')->comment('商家ID');
            $table->unsignedBigInteger('store_id')->nullable()->comment('门店ID');
            $table->enum('cooperation_mode', ['self_operate', 'joint_venture', 'receivables_assignment'])
                  ->comment('合作模式：商家/联营/平台');
            $table->string('product_name')->comment('商品标准名快照');
            $table->string('device_code', 64)->nullable()->comment('设备唯一码 IMEI/SN/车架号');
            $table->bigInteger('device_value_cents')->comment('设备价值（分）');
            $table->bigInteger('deposit_cents')->default(0)->comment('保证金（分）');
            $table->unsignedSmallInteger('periods')->comment('期数');
            $table->bigInteger('period_rent_cents')->comment('每期租金（分）');
            $table->bigInteger('total_amount_cents')->comment('总额（分）');
            $table->unsignedBigInteger('price_snapshot_id')->nullable()->comment('报价快照ID');
            $table->string('status', 32)->default('created')->comment('订单状态');
            $table->string('esign_id')->nullable()->comment('签约ID');
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('merchant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_orders');
    }
};
