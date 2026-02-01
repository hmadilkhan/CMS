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
        Schema::table('department_notes', function (Blueprint $table) {
            $table->tinyInteger('show_to_customer')->default(1)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_notes', function (Blueprint $table) {
            $table->dropColumn('show_to_customer');
        });
    }
};
