<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "TPO Credit Split" on a finance option: does its customer form collect a
 * Solar TPO Credit + Battery TPO Credit and a Customer Portion?
 *
 * It used to be hard-coded to Prepaid PPA and Wheelhouse Credit Union (ids
 * 9 / 10, or those names), so a new option could not have it. Those two are
 * switched on here - matched the same way the old check matched them - and
 * every other option starts off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->boolean('uses_tpo_split')->default(false)->after('milestone_enabled');
        });

        DB::table('finance_options')
            ->whereIn('id', [9, 10])
            ->orWhereRaw('LOWER(TRIM(name)) IN (?, ?)', ['prepaid ppa', 'wheelhouse credit union'])
            ->update(['uses_tpo_split' => true]);
    }

    public function down(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->dropColumn('uses_tpo_split');
        });
    }
};
