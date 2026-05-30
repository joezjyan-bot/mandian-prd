<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 给 yzz_orders 补两列,支撑结算硬前置(全局/02 状态字典 §0.1 + §5.1)。
 * - need_lock:该订单是否需要监管锁(苹果类=true;无锁品类=false)。
 *   依据:§0.1 监管锁校验 + 文档第4章"无锁品类不因没有监管锁卡住结算"。
 * - received_at:客户签收确认时间(PENDING_RECEIPT_CONFIRM → PENDING_LOCK_VERIFY 时写入)。
 *   依据:§0.1 待客户签收确认状态要求记录签收记录。
 * 不改动已建表结构,仅增量加列(与状态机结算硬前置直接相关)。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('yzz_orders', function (Blueprint $table) {
            $table->boolean('need_lock')->default(false)->after('device_code')
                  ->comment('是否需要监管锁:苹果类true/无锁品类false');
            $table->timestamp('received_at')->nullable()->after('delivered_at')
                  ->comment('客户签收确认时间');
        });
    }

    public function down(): void
    {
        Schema::table('yzz_orders', function (Blueprint $table) {
            $table->dropColumn(['need_lock', 'received_at']);
        });
    }
};
