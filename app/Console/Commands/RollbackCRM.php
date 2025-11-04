<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RollbackCRM extends Command
{
    protected $signature = 'deploy:rollback';
    protected $description = 'Rollback to last stable CRM version';

    public function handle()
    {
        $scriptPath = '/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal/rollback.sh';

        if (!file_exists($scriptPath)) {
            $this->error("❌ Script not found: $scriptPath");
            return 1;
        }

        $this->info("⏪ Running rollback...");
        $output = shell_exec("bash $scriptPath 2>&1");

        $this->info("✅ Rollback complete. Log:");
        $this->line($output);

        return 0;
    }
}
