<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A project's current zone. NULL means the project was never enrolled in the
 * Zones module - only projects that reach Deal Review get pulled in, so most of
 * the historic backlog deliberately stays out.
 *
 * `zone_entered_at` is what the board's "days in zone" counter reads; it is
 * rewritten on every zone move.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('zone_id')->nullable()->after('sub_department_id');
            $table->timestamp('zone_entered_at')->nullable()->after('zone_id');
            $table->index('zone_id');
        });

        // Backfill: only the projects sitting in Deal Review right now enter the
        // module, in Pre NTP. Everything further down the pipeline is left
        // alone - the Funding Manager pulls those in by hand if they want them.
        $preNtpId = DB::table('zones')->where('slug', 'pre_ntp')->value('id');
        $entryDepartmentId = config('zones.entry.department_id', 1);

        if (! $preNtpId) {
            return;
        }

        $now = now();

        DB::table('projects')
            ->whereNull('deleted_at')
            ->where('department_id', $entryDepartmentId)
            ->update(['zone_id' => $preNtpId, 'zone_entered_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['zone_id']);
            $table->dropColumn(['zone_id', 'zone_entered_at']);
        });
    }
};
