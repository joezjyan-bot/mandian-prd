<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 总账分录表（财务总账/分录账）。平台收入、应付商家、服务费、坏账等。
 * 复式记账示范：每笔业务生成借贷两条分录。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no', 40)->comment('凭证号（同一笔业务的借贷分录共享）');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('account_code', 32)->comment('会计科目编码（财务定）');
            $table->string('account_name', 64)->comment('科目名称');
            $table->enum('dc', ['debit', 'credit'])->comment('借/贷');
            $table->bigInteger('amount_cents')->comment('金额（分）');
            $table->string('summary')->nullable()->comment('摘要');
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();

            $table->index('voucher_no');
            $table->index('order_id');
            $table->index('account_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_ledger_entries');
    }
};
