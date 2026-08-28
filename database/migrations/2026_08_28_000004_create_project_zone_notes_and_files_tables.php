<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A zone's own notes and files. They are deliberately NOT stored in
 * department_notes / project_files: those two are read by the department tabs,
 * the customer tracking page and the follow-up chases, and a zone row landing
 * in them would surface in all three.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_zone_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes');
            $table->timestamps();

            $table->index(['project_id', 'zone_id']);
        });

        Schema::create('project_zone_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('filename');
            $table->string('header_text')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_zone_files');
        Schema::dropIfExists('project_zone_notes');
    }
};
