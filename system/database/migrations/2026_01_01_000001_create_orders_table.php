<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 订单业务账(四账之一)。记录订单应收/已收/待收/状态/套餐/账单计划锚点。
 * 注意:本表 ≠ 真实到账,只反映业务口径。金额单位:分。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_no', 32)->unique()->comment('订单号');
            $t->unsignedBigInteger('customer_id')->index();
            $t->unsignedBigInteger('merchant_id')->index();
            $t->unsignedBigInteger('store_id')->nullable()->index();
            $t->unsignedBigInteger('product_id')->index();

            $t->enum('cooperation_mode', ['self_operate', 'joint_venture', 'receivables_assignment'])
              ->index()->comment('self_operate商家 joint_venture联营 receivables_assignment平台');

            $t->string('biz_line', 20)->default('long_rent')->comment('long_rent长租 short_rent短租');

            $t->bigInteger('device_value_cents')->comment('设备价值(分)');
            $t->bigInteger('deposit_cents')->default(0)->comment('保证金(分)');
            $t->bigInteger('first_payment_cents')->default(0)->comment('首期应付(分)');
            $t->bigInteger('period_payment_cents')->default(0)->comment('每期应付(分)');
            $t->unsignedSmallInteger('periods')->default(0)->comment('期数');
            $t->unsignedSmallInteger('min_service_period_months')->default(3)->comment('最低服务期(月)');

            $t->string('status', 40)->default('created')->index()
              ->comment('created/auditing/audit_rejected/contract_signing/paying/delivering/signed_for/settled/in_service/overdue/closed ...');

            $t->json('order_snapshot')->nullable()->comment('下单快照:商品/费用/协议版本/页面版本/时间/IP');
            $t->unsignedBigInteger('quote_snapshot_id')->nullable()->comment('办单助手报价快照ID');

            $t->timestamp('settled_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
