<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A material invoice paid in the Transaction Portal is also logged in Account
 * Transactions, as a payment to the Supplier: one transaction per payment
 * (a part payment gets its own), removed again when that payment is removed or
 * the invoice reopened. `transaction_payment_id` is the invoice payment a row
 * mirrors; the portal hides those rows (the invoice already shows in the
 * Material lane, so listing both would count the money twice).
 *
 * Backfill: every invoice payment already recorded gets its row. Invoices
 * closed by 2026_11_04_000003 without a recorded payment are left out (no paid
 * date or amount is known - the client chose to leave them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_payment_id')->nullable()->after('project_invoice_detail_id');
            $table->index('transaction_payment_id');
        });

        $payments = DB::table('transaction_payments as p')
            ->join('project_invoice_details as i', 'i.id', '=', 'p.project_invoice_detail_id')
            ->whereNull('p.deleted_at')
            ->whereNull('i.deleted_at')
            ->where('i.invoice_type', 'material')
            ->get(['p.id', 'p.amount', 'p.paid_on', 'i.id as invoice_id', 'i.project_id', 'i.original_file_name']);

        foreach ($payments as $p) {
            DB::table('account_transactions')->insert([
                'project_id' => $p->project_id,
                'project_invoice_detail_id' => $p->invoice_id,
                'transaction_payment_id' => $p->id,
                'payee' => 'supplier',
                'milestone' => 'Material Invoice',
                'amount' => -round((float) $p->amount, 2),
                'deduction_amount' => 0,
                'transaction_date' => $p->paid_on,
                'transaction_details' => 'Material invoice paid'.($p->original_file_name ? ' - '.$p->original_file_name : ''),
                'status' => 'Closed',
                'settled_amount' => round((float) $p->amount, 2),
                'settled_date' => $p->paid_on,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('account_transactions')->whereNotNull('transaction_payment_id')->delete();

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_payment_id']);
            $table->dropColumn('transaction_payment_id');
        });
    }
};
