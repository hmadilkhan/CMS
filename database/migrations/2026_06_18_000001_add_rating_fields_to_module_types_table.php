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
            if (!Schema::hasColumn('module_types', 'ptc_rating')) {
                $table->decimal('ptc_rating', 10, 2)->nullable()->after('internal_module_cost');
            }
            if (!Schema::hasColumn('module_types', 'voc_rating')) {
                $table->decimal('voc_rating', 10, 2)->nullable()->after('ptc_rating');
            }
            if (!Schema::hasColumn('module_types', 'isc_rating')) {
                $table->decimal('isc_rating', 10, 2)->nullable()->after('voc_rating');
            }
            if (!Schema::hasColumn('module_types', 'weight')) {
                $table->decimal('weight', 10, 2)->nullable()->after('isc_rating');
            }
            if (!Schema::hasColumn('module_types', 'square_footage')) {
                $table->decimal('square_footage', 10, 2)->nullable()->after('weight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_types', function (Blueprint $table) {
            foreach ([
                'ptc_rating',
                'voc_rating',
                'isc_rating',
                'weight',
                'square_footage',
            ] as $column) {
                if (Schema::hasColumn('module_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
