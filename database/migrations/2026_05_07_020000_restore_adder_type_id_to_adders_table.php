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
        if (!Schema::hasColumn('adders', 'adder_type_id')) {
            Schema::table('adders', function (Blueprint $table) {
                $table->integer('adder_type_id')->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('adders', 'adder_type_id')) {
            Schema::table('adders', function (Blueprint $table) {
                $table->dropColumn('adder_type_id');
            });
        }
    }
};
