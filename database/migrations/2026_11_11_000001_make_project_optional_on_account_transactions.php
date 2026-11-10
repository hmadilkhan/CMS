<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Miscellaneous payments: money the company receives that belongs to no
 * project (a lender's bonus, a program rebate, a deposit). They are added in
 * the Transaction Portal's Expected / Actual AR (or imported) with no project,
 * so `project_id` may now be null. The foreign key to projects stays.
 *
 * Raw SQL: this app has no doctrine/dbal, which Laravel 10's ->change() needs.
 * MySQL keeps the foreign key when only the column's NULL-ability changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE account_transactions MODIFY project_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::table('account_transactions')->whereNull('project_id')->exists()) {
            throw new RuntimeException('Some account transactions have no project (miscellaneous payments); give them one or delete them before rolling back.');
        }

        DB::statement('ALTER TABLE account_transactions MODIFY project_id BIGINT UNSIGNED NOT NULL');
    }
};
