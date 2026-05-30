<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 财务总账/分录账(四账之四)。平台收入/应付商家/服务费/坏账/回收/冲正。
 * 本表不给业务页面直接改状态。金额单位:分。
 * TODO[团队/财务]: 科目编码(subject_code)由财务定最终编码。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->string('subject_code', 30)->index()->comment('科目编码,待财务定');
            $t->enum('direction', ['debit', 'credit']);
            $t->bigInteger('amount_cents');
            $t->unsignedBigInteger('order_id')->nullable()->index();
            $t->unsignedBigInteger('payment_flow_id')->nullable();
            $t->string('cooperation_mode', 30)->nullable();
            $t->string('memo', 191)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
