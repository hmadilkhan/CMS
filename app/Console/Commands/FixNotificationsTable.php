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
            // Reset auto-increment to 1
            DB::statement('ALTER TABLE notifications AUTO_INCREMENT = 1');
            
            // Check current status
            $status = DB::select('SHOW TABLE STATUS LIKE "notifications"');
            $autoIncrement = $status[0]->Auto_increment ?? 'Unknown';
            
            $this->info("Notifications table auto-increment reset to: {$autoIncrement}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to fix notifications table: " . $e->getMessage());
            return 1;
        }
    }
}