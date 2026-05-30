<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 钱包/商家账户账(四账之三)。商家可提现/冻结/提现中/返点/分账到账。
 * 余额只由本流水累计得出,不允许直接手改。本表 ≠ 财务总账。金额单位:分。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('merchant_id')->index();
            $t->enum('direction', ['in', 'out']);
            $t->string('entry_type', 30)->comment('monthly_split/order_settlement/platform_deduction/withdrawal/refund_deduction/financial_adjustment ...');
            $t->bigInteger('amount_cents');
            $t->unsignedBigInteger('order_id')->nullable()->index();
            $t->bigInteger('balance_before_cents')->default(0);
            $t->bigInteger('balance_after_cents')->default(0);
            $t->string('status', 20)->default('completed')->comment('pending/completed/failed/reversed');
            $t->string('description', 191)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_entries');
    }
};
