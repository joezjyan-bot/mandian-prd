<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 给 yzz_orders 补 purchase_applied_at 列(申请购买时间)。
 * 依据:C端 12_在租期间 §8 数据模型明确 order.purchase_applied_at(购买申请时间)。
 * 与申请购买流程(EndOfTermService::applyBuyout)直接相关。
 * 归还/续租的 return_request_id / renewal_request_id 关联归还/续租申请表,
 * 属对应模块范围,本次不在此创建。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('yzz_orders', function (Blueprint $table) {
            $table->timestamp('purchase_applied_at')->nullable()->after('settled_at')
                  ->comment('客户申请购买时间(C端12 §8)');
        });
    }

    public function down(): void
    {
        Schema::table('yzz_orders', function (Blueprint $table) {
            $table->dropColumn('purchase_applied_at');
        });
    }
};
