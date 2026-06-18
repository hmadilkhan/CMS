<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->boolean('milestone_enabled')->default(false)->after('dollar_watt_value');
            $table->string('milestone_amount_source')->default('contract_amount')->after('milestone_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->dropColumn(['milestone_enabled', 'milestone_amount_source']);
        });
    }
};
