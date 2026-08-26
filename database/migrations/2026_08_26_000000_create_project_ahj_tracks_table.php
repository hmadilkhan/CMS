<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks every project the Upcoming AHJ's list has shown, so we keep a record
     * of when it left the list - either because a user removed it by hand, or
     * because the project moved on to the Permitting lane.
     */
    public function up(): void
    {
        Schema::create('project_ahj_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->unsignedBigInteger('department_id')->nullable();          // lane it was last seen in
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->string('removed_reason')->nullable();                     // manual | moved_to_permitting | moved_out
            $table->unsignedBigInteger('removed_by')->nullable();             // user who clicked Mark As Remove
            $table->unsignedBigInteger('moved_to_department_id')->nullable(); // lane it moved into
            $table->timestamps();

            $table->unique('project_id');
            $table->index('removed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ahj_tracks');
    }
};
