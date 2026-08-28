<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every zone a project has been in, in order. This is both the audit trail
 * ("who moved it, when, why") and the source of the zone tabs on the project
 * page - a project only shows the zones it has actually visited.
 *
 * `is_auto` separates the two system-driven entries (Deal Review -> Pre NTP,
 * Site Survey -> NTP) from the Funding Manager's own moves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_zone_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('from_zone_id')->nullable();
            $table->unsignedBigInteger('to_zone_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_auto')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'to_zone_id']);
        });

        // Seed the entry movement for the projects the previous migration
        // enrolled, so their Pre NTP tab exists from day one.
        $preNtpId = DB::table('zones')->where('slug', 'pre_ntp')->value('id');

        if (! $preNtpId) {
            return;
        }

        $now = now();

        $rows = DB::table('projects')
            ->whereNull('deleted_at')
            ->where('zone_id', $preNtpId)
            ->pluck('id')
            ->map(fn ($projectId) => [
                'project_id' => $projectId,
                'from_zone_id' => null,
                'to_zone_id' => $preNtpId,
                'user_id' => null,
                'note' => 'Enrolled in Zones.',
                'is_auto' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('project_zone_movements')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_zone_movements');
    }
};
