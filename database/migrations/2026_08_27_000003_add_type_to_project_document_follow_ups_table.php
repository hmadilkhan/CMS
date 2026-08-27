<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * There are now two chases of the same shape - the MPU meter spot result
     * and the Deal Review utility bill - so the table carries the type. Rows
     * written before this migration are all MPU, which the default covers.
     */
    public function up(): void
    {
        Schema::table('project_document_follow_ups', function (Blueprint $table) {
            $table->string('type')->default('mpu')->after('project_id');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('project_document_follow_ups', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
            $table->dropColumn('type');
        });
    }
};
