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
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->float("module_type_cost");
            $table->float("inverter_base_cost");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->dropColumn("module_type_cost");
            $table->dropColumn("inverter_base_cost");
        });
    }
};
