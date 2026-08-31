<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsDeployScript;
use Illuminate\Console\Command;

class DeployCRM extends Command
{
    use RunsDeployScript;

    // Command to RUn Deploy Run
    protected $signature = 'deploy:run';

    protected $description = 'Run CRM deployment script';

    public function handle()
    {
        return $this->runDeployScript('deploy.sh', '🚀 Running deployment...');
    }
}
