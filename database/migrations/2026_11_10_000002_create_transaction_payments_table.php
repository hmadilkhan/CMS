<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part payments. Each row is money actually received or paid against one
 * account transaction or one material invoice, on one day. The parent's
 * `settled_amount` is their total and `settled_date` the last one's day; the
 * parent closes when the total reaches its amount (or is closed short on
 * purpose). See App\Services\TransactionPaymentService.
 *
 * Nothing is backfilled: a row closed before this table existed keeps its
 * settled_amount / settled_date and is shown from those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('project_invoice_detail_id')->nullable()->index();
            $table->decimal('amount', 18, 2);
            $table->date('paid_on')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};
