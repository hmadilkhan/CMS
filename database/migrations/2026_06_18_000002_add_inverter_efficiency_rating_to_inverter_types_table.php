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
        if (Schema::hasColumn('inverter_types', 'inverter_efficiency_rating')) {
            return;
        }

        Schema::table('inverter_types', function (Blueprint $table) {
            $table->decimal('inverter_efficiency_rating', 10, 2)->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('inverter_types', 'inverter_efficiency_rating')) {
            return;
        }

        Schema::table('inverter_types', function (Blueprint $table) {
            $table->dropColumn('inverter_efficiency_rating');
        });
    }
};
