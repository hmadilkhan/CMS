<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A mention raised from a zone note. department_id stays filled (with the
 * project's department at the time) so every existing mention query keeps
 * working; zone_id says which zone note it came from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes_mentions', function (Blueprint $table) {
            $table->unsignedBigInteger('zone_id')->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('notes_mentions', function (Blueprint $table) {
            $table->dropColumn('zone_id');
        });
    }
};
