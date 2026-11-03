<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A zone's own approval date ("M1 Approval Date"), one row per project + zone.
 *
 * Kept off `projects` on purpose: a column per zone would make every new zone a
 * schema change. A zone collects the date when `has_approval_date` is on, and
 * its tab, its finance-milestone trigger and this row all follow from that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->boolean('has_approval_date')->default(false)->after('show_in_list');
        });

        DB::table('zones')->whereIn('slug', ['m1', 'm2'])->update(['has_approval_date' => true]);

        Schema::create('project_zone_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('zone_id');
            $table->date('approval_date')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'zone_id']);
            $table->foreign('project_id', 'pzd_project_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('zone_id', 'pzd_zone_fk')->references('id')->on('zones')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_zone_dates');

        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('has_approval_date');
        });
    }
};
