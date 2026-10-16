<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Departments get an explicit position in the pipeline. Until now the
     * sequence was simply the id, so the backfill numbers every department in
     * id order (soft-deleted ones included, so a restore lands back in place)
     * and nothing changes order the day this runs.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('name')->index();
        });

        $position = 0;
        DB::table('departments')->orderBy('id')->pluck('id')->each(function ($id) use (&$position) {
            DB::table('departments')->where('id', $id)->update(['sort_order' => ++$position]);
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
