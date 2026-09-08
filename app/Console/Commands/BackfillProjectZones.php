<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Task;
use App\Services\ZoneService;
use Illuminate\Console\Command;

/**
 * The one-off catch-up that puts the existing backlog on the funding board.
 *
 * The Zones module only ever enrolled projects from Deal Review onwards, so
 * everything already further down the pipeline sits on no lane. This places
 * each of those where its operations department says it belongs - Deal Review
 * in Pre NTP, anything before Installation in NTP, Installation and Inspection
 * in M1, anything after Inspection in M2 - and leaves the archive out, since
 * "active" means everything not archived in operations.
 *
 * What it never does: touch a project that already has a zone (the Funding
 * Manager's own placement outranks this), and change how new projects get
 * their zone - the two automatic rules in ZoneService are untouched, so from
 * now on projects move exactly as they do today.
 *
 * Safe to run twice: the second run finds nothing left to place.
 */
class BackfillProjectZones extends Command
{
    protected $signature = 'zones:backfill
        {--dry-run : Show what would be placed without writing anything}
        {--limit= : Stop after this many projects}';

    protected $description = 'Place existing active projects on the funding Zones board, from the department they are in.';

    public function handle(ZoneService $zones): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Project::query()
            ->whereNull('zone_id')
            ->whereNotIn('department_id', (array) config('zones.backfill.skip_departments', []))
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $placed = [];
        $skipped = 0;
        $total = 0;

        foreach ($query->cursor() as $project) {
            $total++;
            $zone = $zones->backfillZoneFor((int) $project->department_id);

            if (! $zone) {
                $skipped++;

                continue;
            }

            $placed[$zone->name] = ($placed[$zone->name] ?? 0) + 1;

            if ($dryRun) {
                continue;
            }

            $zones->backfill(
                $project,
                $zone,
                $this->reachedCurrentDepartmentAt($project),
                'Placed on the board from the project\'s department.'
            );
        }

        foreach ($placed as $zone => $count) {
            $this->line(sprintf('%-10s %d', $zone, $count));
        }

        if ($skipped > 0) {
            $this->line(sprintf('%-10s %d (no zone for their department)', 'skipped', $skipped));
        }

        $this->info(sprintf(
            '%s %d of %d project(s) with no zone.',
            $dryRun ? 'Would place' : 'Placed',
            array_sum($placed),
            $total
        ));

        return self::SUCCESS;
    }

    /**
     * When the project reached the stage it is in now - the first task opened
     * for it in its current department. It stands in for the zone's own clock,
     * so the board's "in this zone" reads as the age of the real stage rather
     * than as the moment this command happened to run.
     */
    private function reachedCurrentDepartmentAt(Project $project): ?\DateTimeInterface
    {
        return Task::where('project_id', $project->id)
            ->where('department_id', $project->department_id)
            ->orderBy('id')
            ->value('created_at');
    }
}
