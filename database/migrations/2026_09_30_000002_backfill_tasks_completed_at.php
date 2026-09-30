<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruct `completed_at` for the task rows that were written before the
 * column existed.
 *
 * A project's tasks are a chain: closing a row and creating its successor
 * happen in the same transaction (every move, every reassignment, every design
 * details save), so the successor's `created_at` IS the moment the closed row
 * ended. That is a far better source than `updated_at`, which later writes
 * move forward - it is what made one project report 36 days in Deal Review for
 * two stints that really lasted well under a minute each.
 *
 * Soft-deleted rows are read too: a deleted row still marks when its
 * predecessor ended.
 *
 * The last row of each chain has no successor, so it keeps the old
 * approximation: still open (`In-Progress`) means no exit yet, anything else
 * falls back to `updated_at`. Those rows are the only ones this cannot repair.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tasks')
            ->select('project_id')
            ->distinct()
            ->orderBy('project_id')
            ->chunk(200, function ($projects) {
                foreach ($projects as $project) {
                    $this->backfillProject($project->project_id);
                }
            });
    }

    public function down(): void
    {
        // Intentionally a no-op: the column itself is dropped by the migration
        // that added it. Blanking `completed_at` here would also destroy the
        // values stamped by the application since this ran.
    }

    private function backfillProject($projectId): void
    {
        $tasks = DB::table('tasks')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get(['id', 'status', 'created_at', 'updated_at', 'completed_at']);

        $count = $tasks->count();

        foreach ($tasks as $index => $task) {
            // Never overwrite a value the application already stamped.
            if (! empty($task->completed_at)) {
                continue;
            }

            $successor = $index + 1 < $count ? $tasks[$index + 1] : null;

            $exit = $successor
                ? $successor->created_at
                : ($task->status === 'In-Progress' ? null : $task->updated_at);

            // A clock skew or a hand-edited row must not produce a negative
            // stint; fall back to the entry date so the row reads as instant
            // rather than as time travel.
            if ($exit !== null && $task->created_at !== null && $exit < $task->created_at) {
                $exit = $task->created_at;
            }

            if ($exit === null) {
                continue;
            }

            DB::table('tasks')->where('id', $task->id)->update(['completed_at' => $exit]);
        }
    }
};
