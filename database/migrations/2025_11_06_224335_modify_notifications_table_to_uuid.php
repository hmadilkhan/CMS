<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup existing data
        DB::statement('CREATE TABLE notifications_backup AS SELECT * FROM notifications');
        
        // Drop the table
        Schema::dropIfExists('notifications');
        
        // Recreate with UUID
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        
        // Restore data with new UUIDs
        DB::statement("
            INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at)
            SELECT UUID(), type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at
            FROM notifications_backup
        ");
        
        // Drop backup table
        DB::statement('DROP TABLE notifications_backup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backup data
        DB::statement('CREATE TABLE notifications_backup AS SELECT * FROM notifications');
        
        // Drop and recreate with bigIncrements
        Schema::dropIfExists('notifications');
        
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        
        // Restore data
        DB::statement("
            INSERT INTO notifications (type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at)
            SELECT type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at
            FROM notifications_backup
        ");
        
        DB::statement('DROP TABLE notifications_backup');
    }
};
