<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_design_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('ahj')->nullable();
            $table->string('roof_area')->nullable();
            $table->string('mod')->nullable();
            $table->string('array_area')->nullable();
            $table->string('inv')->nullable();
            $table->string('utility_meter')->nullable();
            $table->string('kw_rating')->nullable();
            $table->string('ac_cec')->nullable();
            $table->string('apn')->nullable();
            $table->string('stories')->nullable();
            $table->string('roof_type')->nullable();
            $table->string('rafter')->nullable();
            $table->string('slope')->nullable();
            $table->string('msp')->nullable();
            $table->string('array_azi')->nullable();
            $table->text('design_notes')->nullable();
            $table->text('assign_notes')->nullable();
            $table->boolean('follow_up')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_design_details');
    }
};
