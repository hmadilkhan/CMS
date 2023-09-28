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
        Schema::table('projects', function (Blueprint $table) {
            $table->float("actual_permit_fee")->nullable();
            $table->float("actual_labor_cost")->nullable();
            $table->float("actual_material_cost")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn("actual_permit_fee");
            $table->dropColumn("actual_labor_cost");
            $table->dropColumn("actual_material_cost");
        });
    }
};
