<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 修正 yzz_bills.bill_type 枚举,对齐状态字典 §6.7。
 * 文档 §6.7:bill_type = first / rent / service / notary / purchase / diff(原 buyout 已废弃)。
 * 原 migration 用的是 first/rent/service_fee/notary_fee/buyout/makeup,与文档不符,
 * 且 OrderService/EndOfTermService 已按文档写入 'first'/'rent'/'purchase',
 * 若不改 enum,'purchase' 等值会被 MySQL enum 拒绝。
 *
 * 用原生 SQL MODIFY COLUMN 改 enum(避免依赖 doctrine/dbal)。
 * 同时把可能存在的旧演示数据值迁移到新枚举,避免改 enum 时旧值丢失。
 */
return new class extends Migration {
    public function up(): void
    {
        // 先放宽为 varchar 以便安全迁移旧值
        DB::statement("ALTER TABLE yzz_bills MODIFY COLUMN bill_type VARCHAR(24) NOT NULL COMMENT '账单类型'");
        // 旧值 → 新值(若有演示数据)
        DB::table('yzz_bills')->where('bill_type', 'service_fee')->update(['bill_type' => 'service']);
        DB::table('yzz_bills')->where('bill_type', 'notary_fee')->update(['bill_type' => 'notary']);
        DB::table('yzz_bills')->where('bill_type', 'buyout')->update(['bill_type' => 'purchase']);
        DB::table('yzz_bills')->where('bill_type', 'makeup')->update(['bill_type' => 'diff']);
        // 收紧为文档 §6.7 枚举
        DB::statement("ALTER TABLE yzz_bills MODIFY COLUMN bill_type ENUM('first','rent','service','notary','purchase','diff') NOT NULL COMMENT '账单类型(§6.7)'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE yzz_bills MODIFY COLUMN bill_type VARCHAR(24) NOT NULL");
        DB::table('yzz_bills')->where('bill_type', 'service')->update(['bill_type' => 'service_fee']);
        DB::table('yzz_bills')->where('bill_type', 'notary')->update(['bill_type' => 'notary_fee']);
        DB::table('yzz_bills')->where('bill_type', 'purchase')->update(['bill_type' => 'buyout']);
        DB::table('yzz_bills')->where('bill_type', 'diff')->update(['bill_type' => 'makeup']);
        DB::statement("ALTER TABLE yzz_bills MODIFY COLUMN bill_type ENUM('first','rent','service_fee','notary_fee','buyout','makeup') NOT NULL");
    }
};
