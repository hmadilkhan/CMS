<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The invoice a system-created payable was raised for. It is what keeps the
 * Labor Payable from being created twice for the same invoice, even after the
 * transaction itself has been deleted by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('project_invoice_detail_id')->nullable()->after('project_id');
            $table->index('project_invoice_detail_id');
        });
    }

    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex(['project_invoice_detail_id']);
            $table->dropColumn('project_invoice_detail_id');
        });
    }
};
