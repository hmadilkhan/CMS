<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mentions are now resolved against users instead of employees, because a
     * project's sales partner user does not always have an employee record.
     */
    public function up(): void
    {
        Schema::table('notes_mentions', function (Blueprint $table) {
            $table->integer('user_id')->nullable()->after('employee_id');
        });

        // MODIFY is MySQL-only syntax and fails on SQLite, which the AI chat tests
        // run on. Skipping it there is harmless: those tests never insert a
        // mention without an employee_id.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `notes_mentions` MODIFY `employee_id` INT NULL');
        }

        DB::statement('UPDATE `notes_mentions` SET `user_id` = (SELECT `user_id` FROM `employees` WHERE `employees`.`id` = `notes_mentions`.`employee_id`) WHERE `user_id` IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes_mentions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        DB::statement('UPDATE `notes_mentions` SET `employee_id` = 0 WHERE `employee_id` IS NULL');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `notes_mentions` MODIFY `employee_id` INT NOT NULL');
        }
    }
};
