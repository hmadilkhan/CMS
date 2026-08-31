<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsDeployScript;
use Illuminate\Console\Command;

class RollbackCRM extends Command
{
    use RunsDeployScript;

    protected $signature = 'deploy:rollback';

    protected $description = 'Rollback to last stable CRM version';

    public function handle()
    {
        return $this->runDeployScript('rollback.sh', '⏪ Running rollback...');
    }
}
