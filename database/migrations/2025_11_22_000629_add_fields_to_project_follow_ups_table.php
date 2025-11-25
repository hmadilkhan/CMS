<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_follow_ups', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('employee_id')->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->after('created_by')->constrained()->onDelete('set null');
            $table->foreignId('sub_department_id')->nullable()->after('department_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_follow_ups', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn(['created_by', 'department_id', 'sub_department_id']);
        });
    }
};
