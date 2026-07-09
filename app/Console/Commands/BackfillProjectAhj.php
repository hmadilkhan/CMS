<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\AhjRegistryService;
use Illuminate\Console\Command;

class BackfillProjectAhj extends Command
{
    protected $signature = 'projects:backfill-ahj {--overwrite : Replace existing AHJ name and website URL} {--limit= : Maximum projects to process}';

    protected $description = 'Fetch AHJ name and website URL for existing projects from AHJ Registry.';

    public function handle(AhjRegistryService $ahjRegistry): int
    {
        $query = Project::with('customer')->whereHas('customer');

        if (! $this->option('overwrite')) {
            $query->where(function ($query) {
                $query->whereNull('ahj')
                    ->orWhere('ahj', '')
                    ->orWhereNull('ahj_website_url')
                    ->orWhere('ahj_website_url', '');
            });
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $updated = 0;
        $processed = 0;

        $query->orderBy('id')->get()->each(function (Project $project) use ($ahjRegistry, &$updated, &$processed) {
            $processed++;

            if ($ahjRegistry->assignToProject($project, $project->customer, (bool) $this->option('overwrite'))) {
                $updated++;
                $this->line("Updated project {$project->id}: {$project->ahj}");
            }
        });

        $this->info("Processed {$processed} project(s). Updated {$updated} project(s).");

        return self::SUCCESS;
    }
}
