<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A transaction is Open until Finance closes it. New rows start Open; every row
 * that exists when this runs (soft-deleted ones included) is already settled
 * history, so it is closed here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->string('status', 10)->default('Open')->after('transaction_details');
        });

        DB::table('account_transactions')->update(['status' => 'Closed']);
    }

    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
