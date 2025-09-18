<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixNotificationsTable extends Command
{
    protected $signature = 'fix:notifications-table';
    protected $description = 'Fix notifications table auto-increment issue';

    public function handle()
    {
        try {
            // Get current max ID
            $maxId = DB::table('notifications')->max('id') ?? 0;
            $this->info("Current max ID: {$maxId}");
            
            // Reset auto-increment to max ID + 1
            $nextId = $maxId + 1;
            DB::statement("ALTER TABLE notifications AUTO_INCREMENT = {$nextId}");
            
            // If still problematic, truncate and reset
            if ($nextId > 1000000) {
                $this->warn('Auto-increment too high, truncating table...');
                DB::statement('TRUNCATE TABLE notifications');
                DB::statement('ALTER TABLE notifications AUTO_INCREMENT = 1');
                $this->info('Notifications table truncated and reset');
            }
            
            // Check final status
            $status = DB::select('SHOW TABLE STATUS LIKE "notifications"');
            $autoIncrement = $status[0]->Auto_increment ?? 'Unknown';
            
            $this->info("Notifications table auto-increment set to: {$autoIncrement}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to fix notifications table: " . $e->getMessage());
            return 1;
        }
    }
}