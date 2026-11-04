<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A material invoice is paid to the supplier straight from the Transaction
 * Portal, so the invoice carries its own Open / Closed status and what was
 * paid when. Labor invoices get the column too but are paid through their
 * Labor Payable transaction, not here.
 *
 * Every invoice already on file is Closed, the way the account transactions
 * status migration closed every existing transaction: only invoices uploaded
 * from now on start Open and appear as payable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_invoice_details', function (Blueprint $table) {
            $table->string('status', 10)->default('Open')->after('amount');
            $table->decimal('settled_amount', 18, 2)->nullable()->after('status');
            $table->date('settled_date')->nullable()->after('settled_amount');
        });

        DB::table('project_invoice_details')->update(['status' => 'Closed']);
    }

    public function down(): void
    {
        Schema::table('project_invoice_details', function (Blueprint $table) {
            $table->dropColumn(['status', 'settled_amount', 'settled_date']);
        });
    }
};
