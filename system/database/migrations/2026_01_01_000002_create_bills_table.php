<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 账单:客户应付计划与实付状态。账单生成后禁止直接改金额,
 * 只能走补收/减免/退款/冲正/核销(见 PRD 财务口径)。金额单位:分。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id')->index();
            $t->unsignedSmallInteger('period_no')->default(0)->comment('期次,0=首期');
            $t->string('bill_type', 20)->comment('first首期 rent租金 service服务费 buyout购买 deposit保证金 overdue逾期');
            $t->bigInteger('amount_due_cents')->comment('应付(分)');
            $t->bigInteger('amount_paid_cents')->default(0)->comment('已付(分)');
            $t->bigInteger('amount_refunded_cents')->default(0)->comment('已退(分)');
            $t->timestamp('due_time')->nullable();
            $t->timestamp('paid_time')->nullable();
            $t->string('status', 20)->default('unpaid')->index()->comment('unpaid/part_paid/paid/refunded/written_off');
            $t->unsignedBigInteger('price_snapshot_id')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
