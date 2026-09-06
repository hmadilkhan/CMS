<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A report can group two levels deep (finance option, then department),
     * and each numeric column can say how it is summarised - summed by
     * default, or averaged, or its lowest or highest value.
     */
    public function up(): void
    {
        Schema::table('saved_reports', function (Blueprint $table) {
            $table->string('group_by_2')->nullable()->after('group_by');
            $table->json('summaries')->nullable()->after('group_by_2');
        });
    }

    public function down(): void
    {
        Schema::table('saved_reports', function (Blueprint $table) {
            $table->dropColumn(['group_by_2', 'summaries']);
        });
    }
};
