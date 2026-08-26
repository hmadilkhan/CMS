<?php

namespace App\Services;

use App\Jobs\SendProjectDateUpdatedEmailJob;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Emails the project's sales person whenever one of the milestone dates below
 * is entered or changed. The date columns are written through query-builder
 * mass updates (which skip model events), so every write site calls this
 * service explicitly with the values it had before the update.
 */
class ProjectDateChangeNotifier
{
    /**
     * Date columns the sales person is notified about, with the wording used
     * for each one. `set` is a first-time entry, `updated` is a date that
     * moved, `cleared` is a date that was emptied. `:date` / `:old` are
     * replaced with the formatted dates.
     */
    public const TRACKED_FIELDS = [
        'permitting_approval_date' => [
            'label' => 'Permit Approval Date',
            'set' => [
                'headline' => 'Permit Approved',
                'message' => 'The permit for this project has been approved, effective :date.',
            ],
            'updated' => [
                'headline' => 'Permit Approval Date Revised',
                'message' => 'The permit approval date has been revised from :old to :date.',
            ],
            'cleared' => [
                'headline' => 'Permit Approval Date Removed',
                'message' => 'The permit approval date (:old) has been removed from this project.',
            ],
        ],
        'solar_install_date' => [
            'label' => 'Installation Date',
            'set' => [
                'headline' => 'Installation Scheduled',
                'message' => 'The installation for this project has been scheduled for :date.',
            ],
            'updated' => [
                'headline' => 'Installation Rescheduled',
                'message' => 'The installation has been rescheduled from :old to :date.',
            ],
            'cleared' => [
                'headline' => 'Installation Date Removed',
                'message' => 'The installation date (:old) has been removed from this project.',
            ],
        ],
        'inspection_approval_date' => [
            'label' => 'Inspection Approval Date',
            'set' => [
                'headline' => 'Inspection Approved',
                'message' => 'The inspection for this project has been approved, effective :date.',
            ],
            'updated' => [
                'headline' => 'Inspection Approval Date Revised',
                'message' => 'The inspection approval date has been revised from :old to :date.',
            ],
            'cleared' => [
                'headline' => 'Inspection Approval Date Removed',
                'message' => 'The inspection approval date (:old) has been removed from this project.',
            ],
        ],
        'pto_approval_date' => [
            'label' => 'PTO Approval Date',
            'set' => [
                'headline' => 'PTO Approved',
                'message' => 'PTO (Permission to Operate) has been approved, effective :date. The system is now cleared to operate.',
            ],
            'updated' => [
                'headline' => 'PTO Approval Date Revised',
                'message' => 'The PTO approval date has been revised from :old to :date.',
            ],
            'cleared' => [
                'headline' => 'PTO Approval Date Removed',
                'message' => 'The PTO approval date (:old) has been removed from this project.',
            ],
        ],
    ];

    /**
     * @param  array<string, mixed>  $original  values held before the update
     * @param  array<string, mixed>  $updates  payload that was written
     */
    public function notify(Project $project, array $original, array $updates): void
    {
        $changes = $this->changes($original, $updates);

        if (empty($changes)) {
            return;
        }

        $salesPerson = $project->salesPartnerUser;

        if (! $salesPerson || empty($salesPerson->email)) {
            Log::warning('Project date updated but the project has no sales person email.', [
                'project_id' => $project->id,
                'fields' => array_column($changes, 'field'),
            ]);

            return;
        }

        try {
            SendProjectDateUpdatedEmailJob::dispatch(
                $salesPerson,
                $project,
                $changes,
                auth()->user()->name ?? 'System'
            )->afterCommit();
        } catch (\Throwable $exception) {
            Log::error('Project date update email failed to queue.', [
                'project_id' => $project->id,
                'user_id' => $salesPerson->id,
                'fields' => array_column($changes, 'field'),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Tracked fields whose value actually moved, each already worded for the
     * email. A field missing from the update payload is left alone, so a
     * department that never touches it can never trigger a mail.
     *
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $updates
     * @return array<int, array{field: string, label: string, event: string, headline: string, message: string, old: ?string, new: ?string}>
     */
    public function changes(array $original, array $updates): array
    {
        $changes = [];

        foreach (self::TRACKED_FIELDS as $field => $copy) {
            if (! array_key_exists($field, $updates)) {
                continue;
            }

            $old = $this->normalise($original[$field] ?? null);
            $new = $this->normalise($updates[$field]);

            if ($old === $new) {
                continue;
            }

            $event = match (true) {
                $new === null => 'cleared',
                $old === null => 'set',
                default => 'updated',
            };

            $changes[] = [
                'field' => $field,
                'label' => $copy['label'],
                'event' => $event,
                'headline' => $copy[$event]['headline'],
                'message' => strtr($copy[$event]['message'], [
                    ':date' => $this->forHumans($new) ?? 'no date',
                    ':old' => $this->forHumans($old) ?? 'no date',
                ]),
                'old' => $old,
                'new' => $new,
            ];
        }

        return $changes;
    }

    /**
     * These columns are varchar, not date, so values are compared as trimmed
     * Y-m-d strings and an empty string counts as "not set".
     */
    protected function normalise(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Reading format for the email body, e.g. "Monday, 24 June 2024".
     */
    protected function forHumans(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('l, j F Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
