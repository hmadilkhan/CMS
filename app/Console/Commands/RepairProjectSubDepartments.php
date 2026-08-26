<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repairs projects whose sub_department_id belongs to a DIFFERENT department
 * than the project's own department_id.
 *
 * Cause: CustomerController::update() used to write the starting (department 1)
 * sub-department onto every project it touched, even ones already moved forward,
 * producing pairs like department 3 + sub-department 1 ("New Deals"). The write
 * has been guarded; this command fixes the rows it already corrupted.
 *
 * Resolution order for the correct sub-department:
 *   1. the sub_department_id of the project's latest task in the SAME department
 *   2. the first sub-department of that department, by `order`
 */
class RepairProjectSubDepartments extends Command
{
    protected $signature = 'projects:repair-sub-departments
                            {--dry-run : Show what would change without writing}
                            {--id=* : Only repair these project ids}';

    protected $description = 'Fix projects whose sub_department_id does not belong to their department_id.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ids = array_filter((array) $this->option('id'));

        $query = DB::table('projects')
            ->join('sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id')
            ->whereColumn('projects.department_id', '!=', 'sub_departments.department_id')
            ->whereNull('projects.deleted_at');

        if (! empty($ids)) {
            $query->whereIn('projects.id', $ids);
        }

        $broken = $query->orderBy('projects.id')->get([
            'projects.id',
            'projects.code',
            'projects.department_id',
            'projects.sub_department_id',
            'sub_departments.name as current_sub_name',
            'sub_departments.department_id as current_sub_owner',
        ]);

        if ($broken->isEmpty()) {
            $this->info('No mismatched projects found. Nothing to repair.');

            return self::SUCCESS;
        }

        $this->warn($broken->count().' mismatched project(s) found.'.($dryRun ? ' (dry run — nothing will be written)' : ''));

        $rows = [];
        $repaired = 0;
        $skipped = 0;

        foreach ($broken as $project) {
            $target = $this->resolveSubDepartment($project->id, $project->department_id);

            if (! $target) {
                $skipped++;
                $rows[] = [
                    $project->id,
                    $project->code,
                    $project->department_id,
                    $project->sub_department_id.' ('.$project->current_sub_name.')',
                    'SKIPPED — department has no sub-departments',
                ];

                continue;
            }

            if (! $dryRun) {
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['sub_department_id' => $target->id]);
            }

            $repaired++;
            $rows[] = [
                $project->id,
                $project->code,
                $project->department_id,
                $project->sub_department_id.' ('.$project->current_sub_name.')',
                $target->id.' ('.$target->name.') via '.$target->source,
            ];
        }

        $this->table(
            ['Project', 'Code', 'Dept', 'Was', $dryRun ? 'Would become' : 'Now'],
            $rows
        );

        $this->info(($dryRun ? 'Would repair ' : 'Repaired ').$repaired.' project(s).'.($skipped ? " Skipped {$skipped}." : ''));

        return self::SUCCESS;
    }

    /**
     * @return object{id:int,name:string,source:string}|null
     */
    protected function resolveSubDepartment(int $projectId, int $departmentId): ?object
    {
        $fromTask = DB::table('tasks')
            ->join('sub_departments', 'tasks.sub_department_id', '=', 'sub_departments.id')
            ->where('tasks.project_id', $projectId)
            ->where('tasks.department_id', $departmentId)
            ->where('sub_departments.department_id', $departmentId)
            ->whereNull('tasks.deleted_at')
            ->orderByDesc('tasks.id')
            ->first(['sub_departments.id', 'sub_departments.name']);

        if ($fromTask) {
            return (object) ['id' => $fromTask->id, 'name' => $fromTask->name, 'source' => 'latest task'];
        }

        $fallback = DB::table('sub_departments')
            ->where('department_id', $departmentId)
            ->orderBy('order', 'asc')
            ->first(['id', 'name']);

        if ($fallback) {
            return (object) ['id' => $fallback->id, 'name' => $fallback->name, 'source' => 'first by order'];
        }

        return null;
    }
}
