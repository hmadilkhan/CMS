<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What closing a transaction records: the amount that actually arrived (money
 * in) or went out (money out) and the day it did. The Transaction Portal's
 * Actual AR lists money-in rows by `settled_date`.
 *
 * `due_date` is when a payable must be paid - set on the commission payable
 * (Friday of the week after the payment arrived). Null means no due date.
 *
 * Rows closed before this existed keep both settlement columns null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->decimal('settled_amount', 18, 2)->nullable()->after('status');
            $table->date('settled_date')->nullable()->after('settled_amount');
            $table->date('due_date')->nullable()->after('transaction_date');
            $table->index('settled_date');
        });
    }

    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex(['settled_date']);
            $table->dropColumn(['settled_amount', 'settled_date', 'due_date']);
        });
    }
};
