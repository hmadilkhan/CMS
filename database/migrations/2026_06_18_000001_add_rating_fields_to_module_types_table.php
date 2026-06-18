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
        Schema::table('module_types', function (Blueprint $table) {
            $table->decimal('ptc_rating', 10, 2)->nullable()->after('internal_module_cost');
            $table->decimal('voc_rating', 10, 2)->nullable()->after('ptc_rating');
            $table->decimal('isc_rating', 10, 2)->nullable()->after('voc_rating');
            $table->decimal('weight', 10, 2)->nullable()->after('isc_rating');
            $table->decimal('square_footage', 10, 2)->nullable()->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_types', function (Blueprint $table) {
            $table->dropColumn([
                'ptc_rating',
                'voc_rating',
                'isc_rating',
                'weight',
                'square_footage',
            ]);
        });
    }
};
