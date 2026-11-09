<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payees can be switched off from Operations -> Payees. An inactive payee is
 * left out of the pickers but still labels the rows that already use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payees', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('payees', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
