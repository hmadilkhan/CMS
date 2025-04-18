<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inverter_type_rates', function (Blueprint $table) {
            $table->float("internal_base_cost")->default(0.00);
            $table->float("internal_labor_cost")->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inverter_type_rates', function (Blueprint $table) {
            $table->dropColumn("internal_base_cost");
            $table->dropColumn("internal_labor_cost");
        });
    }
};
