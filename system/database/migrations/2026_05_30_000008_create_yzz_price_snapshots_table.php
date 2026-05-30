<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 报价快照表。办单助手生成后锁死，客户扫码读这份快照下单。
 * 下单后不可变，后台改配置不影响已生成快照。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('snapshot_no', 40)->unique();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->enum('cooperation_mode', ['self_operate', 'joint_venture', 'receivables_assignment']);
            $table->bigInteger('device_value_cents');
            $table->unsignedSmallInteger('periods');
            $table->unsignedInteger('down_payment_ratio')->default(0);
            $table->bigInteger('deposit_cents')->default(0);
            $table->bigInteger('first_pay_cents')->comment('首期实付');
            $table->bigInteger('period_rent_cents')->comment('每期应付');
            $table->bigInteger('total_amount_cents');
            $table->json('buyout_by_period')->nullable()->comment('每期购买参考价');
            $table->json('value_added_services')->nullable();
            $table->unsignedBigInteger('config_version_id')->default(1);
            $table->string('status', 16)->default('active')->comment('active/used/voided');
            $table->timestamps();

            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_price_snapshots');
    }
};
