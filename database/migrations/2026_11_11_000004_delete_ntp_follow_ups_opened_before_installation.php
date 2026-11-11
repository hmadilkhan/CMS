<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Deletes the NTP approval follow ups that a bug opened too early, and the
 * history lines written when they opened.
 *
 * The NTP chase starts at the move into Installation ('opens_on_move'). Until
 * 2026-11-11 DocumentFollowUpService::syncType() ignored that flag, so any
 * sync() - a department field save, a chase upload - opened one on every
 * project without an NTP approval date, from Deal Review on. Those rows held
 * nothing (moveProject() opens the chase itself at the move and parks the
 * project either way), but they carried a false start date and showed on the
 * project page's Open Follow Ups strip.
 *
 * Deleted: Pending NTP chases on a project whose department comes BEFORE
 * Installation in the pipeline (sort_order, then id - Department::comesBefore()).
 * A project parked in Install Pending Document, or anywhere in Installation or
 * later, keeps its chase. With each deleted chase go:
 *   - its activity_log "document_follow_up_opened" entry (matched by the
 *     document_follow_up_id property), and
 *   - its department_notes line (same project, the exact opening message,
 *     written within a minute of the chase's opened_at).
 *
 * Not reversible: the rows were never valid, so down() does nothing.
 */
return new class extends Migration
{
    private const OPENED_NOTE = '[NTP Approval Follow Up] NTP Approval Follow Up opened: '
        .'the project is moving to Installation and the NTP Approval Date is not on file yet.';

    public function up(): void
    {
        if (! Schema::hasTable('project_document_follow_ups')) {
            return;
        }

        $installation = DB::table('departments')->where('name', 'Installation')->first(['id', 'sort_order']);

        if (! $installation) {
            return;
        }

        $earlierDepartmentIds = DB::table('departments')
            ->get(['id', 'sort_order'])
            ->filter(fn ($department) => [(int) $department->sort_order, (int) $department->id]
                < [(int) $installation->sort_order, (int) $installation->id])
            ->pluck('id')
            ->all();

        if ($earlierDepartmentIds === []) {
            return;
        }

        $followUps = DB::table('project_document_follow_ups as f')
            ->join('projects as p', 'p.id', '=', 'f.project_id')
            ->where('f.type', 'ntp_approval')
            ->where('f.status', 'Pending')
            ->whereIn('p.department_id', $earlierDepartmentIds)
            ->get(['f.id', 'f.project_id', 'f.opened_at']);

        if ($followUps->isEmpty()) {
            return;
        }

        $activityIds = [];
        $noteIds = [];

        foreach ($followUps as $followUp) {
            if (Schema::hasTable('activity_log')) {
                $activityIds = array_merge($activityIds, DB::table('activity_log')
                    ->where('log_name', 'project')
                    ->where('event', 'document_follow_up_opened')
                    ->where('subject_id', $followUp->project_id)
                    ->get(['id', 'properties'])
                    ->filter(function ($activity) use ($followUp) {
                        $properties = json_decode((string) $activity->properties, true) ?: [];

                        return (int) ($properties['document_follow_up_id'] ?? 0) === (int) $followUp->id;
                    })
                    ->pluck('id')
                    ->all());
            }

            if ($followUp->opened_at && Schema::hasTable('department_notes')) {
                $openedAt = \Illuminate\Support\Carbon::parse($followUp->opened_at);

                $noteIds = array_merge($noteIds, DB::table('department_notes')
                    ->where('project_id', $followUp->project_id)
                    ->where('notes', self::OPENED_NOTE)
                    ->whereBetween('created_at', [$openedAt->copy()->subMinute(), $openedAt->copy()->addMinute()])
                    ->pluck('id')
                    ->all());
            }
        }

        DB::transaction(function () use ($followUps, $activityIds, $noteIds) {
            if ($activityIds !== []) {
                DB::table('activity_log')->whereIn('id', $activityIds)->delete();
            }

            if ($noteIds !== []) {
                DB::table('department_notes')->whereIn('id', $noteIds)->delete();
            }

            DB::table('project_document_follow_ups')->whereIn('id', $followUps->pluck('id'))->delete();
        });

        Log::info('Deleted NTP follow ups opened before Installation', [
            'follow_up_ids' => $followUps->pluck('id')->all(),
            'project_ids' => $followUps->pluck('project_id')->all(),
            'activity_log_ids' => $activityIds,
            'department_note_ids' => $noteIds,
        ]);
    }

    public function down(): void
    {
        // Nothing to restore: the deleted rows should never have existed.
    }
};
