<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 办单助手配置表（活的可配置项）。
 * 三种方案分权：商家订单商家自配，联营/平台订单运营配。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_calculator_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['self_operate', 'joint_venture', 'receivables_assignment'])
                  ->comment('适用的合作模式/方案');
            $table->unsignedBigInteger('merchant_id')->nullable()->comment('商家订单时归属商家');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->json('periods')->comment('可选期数，如 [3,6,12]，可增删');
            $table->json('down_payment_ratios')->comment('首付成数可选，如 [0,10,20,30]');
            $table->enum('rate_basis', ['unpaid_amount', 'device_value'])
                  ->default('unpaid_amount')->comment('费率计算方式：未付金额×费率 / 设备价×费率');
            $table->unsignedInteger('rate_bps')->default(0)->comment('费率（万分比）');
            $table->json('value_added_services')->nullable()->comment('增值服务清单（含是否默认/强制勾选）');
            $table->unsignedBigInteger('config_version_id')->default(1)->comment('配置版本快照');
            $table->string('status', 16)->default('on');
            $table->timestamps();

            $table->index(['scope', 'merchant_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_calculator_configs');
    }
};
