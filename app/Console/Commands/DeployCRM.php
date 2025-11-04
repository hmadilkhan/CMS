<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployCRM extends Command
{
    protected $signature = 'deploy:run';
    protected $description = 'Run CRM deployment script';

    public function handle()
    {
        $scriptPath = '/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal/deploy.sh';

        if (!file_exists($scriptPath)) {
            $this->error("❌ Script not found: $scriptPath");
            return 1;
        }

        $this->info("🚀 Running deployment...");
        $output = shell_exec("bash $scriptPath 2>&1");

        $this->info("✅ Deployment finished. Log:");
        $this->line($output);

        return 0;
    }
}
