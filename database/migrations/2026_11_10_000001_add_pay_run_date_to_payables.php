<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Transaction Portal's pay run: the day Finance plans to pay a payable
 * (Commission / Labor on `account_transactions`, Material on
 * `project_invoice_details`). Picked on Thursday for Friday's payments. Once a
 * week has a pay run, its other open payables move to the next week. Null =
 * not in a pay run. Nothing is backfilled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->date('pay_run_date')->nullable()->after('due_date');
            $table->index('pay_run_date');
        });

        Schema::table('project_invoice_details', function (Blueprint $table) {
            $table->date('pay_run_date')->nullable()->after('settled_date');
            $table->index('pay_run_date');
        });
    }

    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex(['pay_run_date']);
            $table->dropColumn('pay_run_date');
        });

        Schema::table('project_invoice_details', function (Blueprint $table) {
            $table->dropIndex(['pay_run_date']);
            $table->dropColumn('pay_run_date');
        });
    }
};
