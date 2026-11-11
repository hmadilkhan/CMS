<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Miscellaneous payments in every Transaction Portal section: a material
 * invoice added there may belong to no project, so `project_id` may now be
 * null (account_transactions got the same on 2026_11_11_000001). The foreign
 * key stays. Everything else that reads invoices does so through a project
 * (the project's Invoices tab, the invoice gates, the commission, the Labor
 * Payable), so an invoice with none is simply not seen there.
 *
 * Raw SQL: this app has no doctrine/dbal, which Laravel 10's ->change() needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE project_invoice_details MODIFY project_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::table('project_invoice_details')->whereNull('project_id')->exists()) {
            throw new RuntimeException('Some invoices have no project (miscellaneous payments); give them one or delete them before rolling back.');
        }

        DB::statement('ALTER TABLE project_invoice_details MODIFY project_id BIGINT UNSIGNED NOT NULL');
    }
};
