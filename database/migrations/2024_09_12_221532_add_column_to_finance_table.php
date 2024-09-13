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
            $table->decimal("total_overwrite_base_price", 18, 2)->default(0.00);
            $table->decimal("total_overwrite_panel_price", 18, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->dropColumn("total_overwrite_base_price");
            $table->dropColumn("total_overwrite_panel_price");
        });
    }
};
