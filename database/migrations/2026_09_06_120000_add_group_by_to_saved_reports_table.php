<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A report can group its rows by one field (finance option, department,
     * sales partner...), which is part of the report, not of a run - so it is
     * saved with it.
     */
    public function up(): void
    {
        Schema::table('saved_reports', function (Blueprint $table) {
            $table->string('group_by')->nullable()->after('selected_fields');
        });
    }

    public function down(): void
    {
        Schema::table('saved_reports', function (Blueprint $table) {
            $table->dropColumn('group_by');
        });
    }
};
