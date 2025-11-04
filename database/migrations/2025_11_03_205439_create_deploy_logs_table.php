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
        Schema::create('deploy_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // deploy or rollback
            $table->string('run_by')->nullable(); // admin user
            $table->text('output')->nullable(); // script output
            $table->string('status')->default('success'); // success, failed
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deploy_logs');
    }
};
