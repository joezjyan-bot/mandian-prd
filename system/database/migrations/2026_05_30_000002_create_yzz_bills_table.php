<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 账单表（属订单业务账）。客户应付计划和实付状态。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedSmallInteger('period_no')->comment('期次，0=首期');
            $table->enum('bill_type', ['first', 'rent', 'service_fee', 'notary_fee', 'buyout', 'makeup'])
                  ->comment('账单类型');
            $table->bigInteger('amount_due_cents')->comment('应付（分）');
            $table->bigInteger('amount_paid_cents')->default(0)->comment('实付（分）');
            $table->bigInteger('amount_refunded_cents')->default(0)->comment('已退（分）');
            $table->timestamp('due_time')->nullable();
            $table->timestamp('paid_time')->nullable();
            $table->string('status', 24)->default('unpaid')->comment('unpaid/partial/paid/overdue');
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_bills');
    }
};
