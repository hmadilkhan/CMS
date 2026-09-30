<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A task row is a project's stint in one department, and the Department Logs
 * tab reports how long each stint lasted. Until now the tab had no exit
 * timestamp to read: it used `updated_at`, which is the row's LAST write, not
 * the moment the project left. Any later write to a closed row pushes that
 * date forward and inflates the stint - `CustomerController::update()` mass
 * updates every department-1 task of a project, so one customer edit re-stamps
 * the whole Deal Review history to the edit time.
 *
 * `completed_at` is stamped once, where the row is actually closed out, and is
 * never touched again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
