<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 钱包流水表（钱包/商家账户账）。商家结算账户余额由流水累计，不直接改。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->comment('商家ID');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('direction', ['in', 'out'])->comment('入/出');
            $table->string('entry_type', 32)->comment('order_settlement/monthly_split/withdrawal/refund_deduction 等');
            $table->bigInteger('amount_cents')->comment('金额（分）');
            $table->bigInteger('balance_before_cents')->default(0);
            $table->bigInteger('balance_after_cents')->default(0);
            $table->string('status', 24)->default('completed')->comment('pending/completed/failed/reversed');
            $table->string('description')->nullable()->comment('摘要（商家端可见）');
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('order_id');
            $table->index('entry_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_wallet_entries');
    }
};
