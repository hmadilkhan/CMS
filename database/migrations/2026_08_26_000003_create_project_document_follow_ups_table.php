<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Document Follow Up list: every project whose MPU Required is "yes" but
     * whose meter spot result is still missing. Kept in its own table so the
     * ordinary project_follow_ups queries (dashboard list, auto-resolve on a
     * lane move, admin dashboard) never touch it - this follow up must survive
     * the project moving from Engineering through Permitting into Installation.
     */
    public function up(): void
    {
        Schema::create('project_document_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id')->nullable();       // Engineering assignee who owns it
            $table->unsignedBigInteger('department_id')->nullable();     // lane the project was in when it opened
            $table->unsignedBigInteger('sub_department_id')->nullable();
            $table->string('status')->default('Pending');                // Pending | Resolved
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('resolved_at')->nullable();                // when it left the list
            $table->string('resolved_reason')->nullable();               // meter_spot_result | mpu_not_required | manual
            $table->unsignedBigInteger('resolved_by')->nullable();       // user who closed it
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_document_follow_ups');
    }
};
