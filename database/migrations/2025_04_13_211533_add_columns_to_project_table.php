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
            $table->decimal("pre_estimated_material_costs", 18, 2)->default(0.00);
            $table->decimal("pre_estimated_labor_costs", 18, 2)->default(0.00);
            $table->decimal("pre_estimated_permit_costs", 18, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn("pre_estimated_material_costs");
            $table->dropColumn("pre_estimated_labor_costs");
            $table->dropColumn("pre_estimated_permit_costs");
        });
    }
};
