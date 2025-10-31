<?php

namespace App\Console\Commands;

use App\Models\ProjectAcceptance;
use App\Models\ProjectAddersLock;
use Illuminate\Console\Command;

class LockApprovedProjectsAdders extends Command
{
    protected $signature = 'adders:lock-approved';
    protected $description = 'Lock adders for all previously approved projects';

    public function handle()
    {
        $approvedProjects = ProjectAcceptance::where('status', 1)
            ->whereHas('project')
            ->with('project')
            ->get();

        $count = 0;
        foreach ($approvedProjects as $acceptance) {
            if ($acceptance->project && !ProjectAddersLock::where('project_id', $acceptance->project_id)->where('status', 'locked')->exists()) {
                ProjectAddersLock::create([
                    'project_id' => $acceptance->project_id,
                    'user_id' => $acceptance->action_by ?? 1,
                    'status' => 'locked'
                ]);
                $count++;
            }
        }

        $this->info("Locked adders for {$count} approved projects.");
        return 0;
    }
}
