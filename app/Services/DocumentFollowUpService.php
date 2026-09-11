<?php

namespace App\Services;

use App\Models\AssignDepartment;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocumentFollowUp;
use App\Models\ProjectFile;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;

/**
 * The paperwork chases.
 *
 * Two of them, identical in shape:
 *
 *   MPU           - Engineering owns it. Opens when MPU Required is "yes" and
 *                   the meter spot result is still missing; clears when the
 *                   result comes in.
 *   Utility Bill  - Deal Review owns it. Opens when Utility Bill Uploaded is
 *                   "no" and no bill has been uploaded; clears when the bill
 *                   itself is uploaded from the follow up card, which also
 *                   turns that field to "yes" - the bill IS the answer to it.
 *   Fire Review   - Permitting owns it. Opens when Fire Review Required is
 *                   "yes" and no fire approval document has been uploaded;
 *                   clears when that document is uploaded from the card.
 *
 * While a chase is open the project may still travel the pipeline normally,
 * but the one move that would carry it past the paperwork is intercepted: it
 * lands in that chase's parked lane instead of the lane the user picked, and
 * that single move sends no assignment e-mail. The parked lane is closed
 * (sub_departments.show_in_move_list = 0), so nothing can be moved out of it
 * by hand. Producing the missing document closes the chase, moves the project
 * on to the release lane, and e-mails the assignee. Answering the department
 * field the other way - the paperwork is not owed after all ("no" for MPU and
 * fire review, "yes" for the utility bill, whose question is whether the bill
 * has already been uploaded) - closes it too, and the project is released just
 * the same so it is never stranded in a closed lane.
 */
class DocumentFollowUpService
{
    public const TYPE_MPU = 'mpu';

    public const TYPE_UTILITY_BILL = 'utility_bill';

    public const TYPE_FIRE_REVIEW = 'fire_review';

    public const TYPE_NTP_APPROVAL = 'ntp_approval';

    /** A project parked here is out of every chase. */
    public const ARCHIVED_DEPARTMENT = 'Archived';

    /**
     * Everything that differs between the chases.
     *
     * `file_category` means the chase is closed by uploading that kind of file;
     * `value_column` means it is closed by filling that project column in, with
     * `value_options` limiting what may be entered. Sub-department ids are fixed
     * records: 31 Install Pending Document, 12 Install Not Scheduled, 32 PTO
     * Pending Document, 18 PTO, 29 Inspection Pending Fire Review, 16 Inspection
     * Not Scheduled.
     */
    public const TYPES = [
        self::TYPE_MPU => [
            'label' => 'Document Follow Up',
            'owner_department' => 'Engineering',
            'from_department' => 'Permitting',
            'to_department' => 'Installation',
            'parked_sub_department_id' => 31,
            'released_sub_department_id' => 12,
            'value_column' => 'meter_spot_result',
            'value_options' => ['same', 'relocation'],
            'file_category' => null,
        ],
        self::TYPE_UTILITY_BILL => [
            'label' => 'Utility Bill Follow Up',
            'owner_department' => 'Deal Review',
            'from_department' => 'Inspection',
            'to_department' => 'PTO',
            'parked_sub_department_id' => 32,
            'released_sub_department_id' => 18,
            'value_column' => null,
            'value_options' => [],
            'file_category' => ProjectFile::CATEGORY_UTILITY_BILL,
            /*
             * The only chase whose department field asks whether the document is
             * already IN rather than whether it is needed, so the files ARE the
             * answer to it and the field follows them in both directions: one
             * bill on the project and "Utility Bill Uploaded" reads Yes; the last
             * one removed and it reads No again, which re-opens the chase. The
             * other chases ask whether paperwork is required, and that answer
             * stays true after the paperwork arrives - nothing to flip there.
             */
            'answered_by_document' => [
                'column' => 'utility_bill_required',
                'value' => 'yes',
                'value_when_missing' => 'no',
                'label' => 'Utility Bill Uploaded',
                'because' => 'the bill was uploaded',
                'because_missing' => 'the uploaded bill was removed',
            ],
        ],
        self::TYPE_FIRE_REVIEW => [
            'label' => 'Fire Review Follow Up',
            'owner_department' => 'Permitting',
            'from_department' => 'Installation',
            'to_department' => 'Inspection',
            'parked_sub_department_id' => 29,
            'released_sub_department_id' => 16,
            'value_column' => null,
            'value_options' => [],
            'file_category' => ProjectFile::CATEGORY_FIRE_REVIEW,
        ],

        /*
         * The NTP approval date. This one is answered by the FUNDING side, from
         * the Zones NTP tab - not by an Operations dashboard card - so it opens
         * at the move it parks rather than the moment the date goes missing
         * ('opens_on_move'), and it has no card of its own.
         *
         * It used to be a refusal instead of a chase: Permitting -> Installation
         * was blocked until someone typed the date into the move modal. The move
         * now goes through and the project waits in Install Pending Document
         * until the Funding Manager files the date.
         */
        self::TYPE_NTP_APPROVAL => [
            'label' => 'NTP Approval Follow Up',
            'owner_department' => 'Permitting',
            'from_department' => 'Permitting',
            'to_department' => 'Installation',
            'parked_sub_department_id' => 31,
            'released_sub_department_id' => 12,
            'value_column' => 'ntp_approval_date',
            'value_options' => [],
            'file_category' => null,
            'opens_on_move' => true,
        ],
    ];

    protected ProjectAssignmentService $assignmentService;

    public function __construct(ProjectAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /* ---------------------------------------------------------------- lookups */

    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    public function config(string $type): array
    {
        return self::TYPES[$type] ?? self::TYPES[self::TYPE_MPU];
    }

    public function label(string $type): string
    {
        return $this->config($type)['label'];
    }

    public function ownerDepartmentId(string $type): ?int
    {
        return Department::where('name', $this->config($type)['owner_department'])->value('id');
    }

    public function fromDepartmentId(string $type): ?int
    {
        return Department::where('name', $this->config($type)['from_department'])->value('id');
    }

    public function toDepartmentId(string $type): ?int
    {
        return Department::where('name', $this->config($type)['to_department'])->value('id');
    }

    public function parkedSubDepartmentId(string $type): int
    {
        return $this->config($type)['parked_sub_department_id'];
    }

    public function releasedSubDepartmentId(string $type): int
    {
        return $this->config($type)['released_sub_department_id'];
    }

    public function archivedDepartmentId(): ?int
    {
        return Department::where('name', self::ARCHIVED_DEPARTMENT)->value('id');
    }

    /**
     * Only the people listed for the owning department in Operations > Assign
     * Department get that chase's dashboard section.
     */
    public function visibleTo(?User $user, string $type): bool
    {
        if (! $user) {
            return false;
        }

        $ownerDepartmentId = $this->ownerDepartmentId($type);

        if (! $ownerDepartmentId) {
            return false;
        }

        $employeeIds = Employee::where('user_id', $user->id)->pluck('id');

        if ($employeeIds->isEmpty()) {
            return false;
        }

        return AssignDepartment::whereIn('employee_id', $employeeIds)
            ->where('department_id', $ownerDepartmentId)
            ->exists();
    }

    /**
     * The live list for one chase, newest first. Reconciles first so projects
     * whose field was set before this feature existed - or through a path that
     * does not call sync() - still show up.
     */
    public function pendingList(string $type)
    {
        $this->syncAll();

        return ProjectDocumentFollowUp::with(['project.customer', 'project.department', 'project.subdepartment'])
            ->ofType($type)
            ->pending()
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($followUp) => $followUp->project !== null)
            ->values();
    }

    public function pendingFor(int $projectId, string $type): ?ProjectDocumentFollowUp
    {
        return ProjectDocumentFollowUp::where('project_id', $projectId)
            ->ofType($type)
            ->pending()
            ->latest('id')
            ->first();
    }

    /** Pass a type to ask about one chase, or nothing to ask about any. */
    public function hasPending(int $projectId, ?string $type = null): bool
    {
        return ProjectDocumentFollowUp::where('project_id', $projectId)
            ->when($type, fn ($query) => $query->ofType($type))
            ->pending()
            ->exists();
    }

    /** The department field says this paperwork is needed on this project. */
    public function paperworkRequired(Project $project, string $type): bool
    {
        return match ($type) {
            // Deal Review's field asks whether the bill is already uploaded, so
            // it is "no" - not "yes" - that means a document is still owed.
            self::TYPE_UTILITY_BILL => strtolower((string) $project->utility_bill_required) === 'no',
            self::TYPE_FIRE_REVIEW => (int) $project->fire_review_required === 1,
            // Every project needs the date eventually; what makes it a chase is
            // the move, so this only says whether the date is still missing.
            self::TYPE_NTP_APPROVAL => trim((string) $project->ntp_approval_date) === '',
            default => strtolower((string) $project->mpu_required) === 'yes',
        };
    }

    /** The paperwork is needed, has not arrived, and the project is still in play. */
    public function needsFollowUp(Project $project, string $type): bool
    {
        if ((int) $project->department_id === (int) $this->archivedDepartmentId()) {
            return false;
        }

        return $this->paperworkRequired($project, $type)
            && ! $this->documentReceived($project, $type);
    }

    /**
     * The missing paperwork has arrived - either the file itself (utility bill,
     * fire approval), which only the follow up card uploads, or the value the
     * chase is waiting on (the meter spot result).
     */
    public function documentReceived(Project $project, string $type): bool
    {
        $config = $this->config($type);

        if ($config['file_category']) {
            return ProjectFile::where('project_id', $project->id)
                ->category($config['file_category'])
                ->exists();
        }

        return trim((string) $project->{$config['value_column']}) !== '';
    }

    /* ------------------------------------------------------------ reconciling */

    /**
     * Bring every chase in step with the project: open one when the field turns
     * bad, close it when the document lands (or the question no longer applies,
     * or the project is archived). Safe to call after any project write.
     */
    public function sync(Project $project, ?User $causer = null): void
    {
        foreach (self::types() as $type) {
            $this->syncType($project, $type, $causer);
        }
    }

    public function syncType(Project $project, string $type, ?User $causer = null): void
    {
        // The documents answer the department field on the way past: this runs
        // before the chase is looked at, and whether or not one is open, so a
        // project whose bill arrived (or was deleted again) through any path
        // still ends up reading what its files say.
        $this->syncFieldToDocuments($project, $type, $causer);

        $pending = $this->pendingFor($project->id, $type);

        if ($this->needsFollowUp($project, $type)) {
            if (! $pending && ! $this->predatesTheChase($project, $type)) {
                $this->open($project, $type, $causer);
            }

            return;
        }

        if (! $pending) {
            return;
        }

        if ((int) $project->department_id === (int) $this->archivedDepartmentId()) {
            $reason = ProjectDocumentFollowUp::REASON_PROJECT_ARCHIVED;
        } elseif ($this->documentReceived($project, $type)) {
            $reason = ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED;
        } else {
            $reason = ProjectDocumentFollowUp::REASON_NOT_REQUIRED;
        }

        $this->resolve($pending, $project, $reason, $causer);

        // Whatever closed the chase, a project still sitting in its parked lane
        // has to be let out: that lane is closed to manual moves, so leaving it
        // there would strand the project with no way forward.
        $this->releaseFromParkedLane($project, $type, $causer);
    }

    /** The chases whose department field is written from their documents. */
    public function typesAnsweredByDocument(): array
    {
        return array_values(array_filter(
            self::types(),
            fn ($type) => ! empty($this->config($type)['answered_by_document'])
        ));
    }

    /**
     * Reconcile every project that either belongs on a list or is on one now.
     * Cheap - the candidate set is only the projects still missing a document
     * plus whatever is already open.
     */
    public function syncAll(?User $causer = null): void
    {
        $candidateIds = Project::query()
            ->where(function ($query) {
                $query->where(function ($mpu) {
                    $mpu->whereRaw('lower(mpu_required) = ?', ['yes'])
                        ->where(function ($missing) {
                            $missing->whereNull('meter_spot_result')->orWhere('meter_spot_result', '');
                        });
                })
                    ->orWhereRaw('lower(utility_bill_required) = ?', ['no'])
                    ->orWhere('fire_review_required', 1);
            })
            ->pluck('id')
            ->merge(ProjectDocumentFollowUp::pending()->pluck('project_id'))
            // A field the documents answered can go stale the moment one of them
            // is deleted, and the project has left the candidate query above by
            // then (its answer is "yes" now), so keep those in the set.
            ->merge(ProjectDocumentFollowUp::whereIn('type', $this->typesAnsweredByDocument())
                ->where('resolved_reason', ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED)
                ->pluck('project_id'))
            ->unique();

        if ($candidateIds->isEmpty()) {
            return;
        }

        foreach (Project::whereIn('id', $candidateIds)->get() as $project) {
            $this->sync($project, $causer);
        }
    }

    /**
     * The project answered this chase's question before the chase existed, so a
     * migration marked it as pre-existing and it is never chased. Removing that
     * row opts the project back in.
     */
    public function predatesTheChase(Project $project, string $type): bool
    {
        return ProjectDocumentFollowUp::where('project_id', $project->id)
            ->ofType($type)
            ->where('resolved_reason', ProjectDocumentFollowUp::REASON_PRE_EXISTING)
            ->exists();
    }

    /** Put the project on the owning department's dashboard list. */
    public function open(Project $project, string $type, ?User $causer = null): ProjectDocumentFollowUp
    {
        $employee = $this->assignmentService->employeeForDepartment((int) $this->ownerDepartmentId($type));

        $followUp = ProjectDocumentFollowUp::create([
            'project_id' => $project->id,
            'type' => $type,
            'employee_id' => $employee?->id,
            'department_id' => $project->department_id,
            'sub_department_id' => $project->sub_department_id,
            'status' => ProjectDocumentFollowUp::STATUS_PENDING,
            'opened_at' => now(),
        ]);

        $why = match ($type) {
            self::TYPE_UTILITY_BILL => 'Utility Bill Uploaded is No and the bill has not been uploaded yet',
            self::TYPE_FIRE_REVIEW => 'Fire Review Required is Yes and no fire approval document has been uploaded yet',
            self::TYPE_NTP_APPROVAL => 'the project is moving to Installation and the NTP Approval Date is not on file yet',
            default => 'MPU Required is Yes and the meter spot result is still missing',
        };

        $this->record(
            $project,
            $type,
            'document_follow_up_opened',
            $this->label($type).' opened: '.$why.'.',
            ['document_follow_up_id' => $followUp->id],
            $causer
        );

        return $followUp;
    }

    /** Take it off the list, keeping the date and time it left (and why). */
    public function resolve(ProjectDocumentFollowUp $followUp, Project $project, string $reason, ?User $causer = null): void
    {
        $resolvedAt = now();
        $type = $followUp->type;

        $followUp->update([
            'status' => ProjectDocumentFollowUp::STATUS_RESOLVED,
            'resolved_at' => $resolvedAt,
            'resolved_reason' => $reason,
            'resolved_by' => $causer?->id ?? auth()->id(),
        ]);

        $why = match ($reason) {
            ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED => match ($type) {
                self::TYPE_UTILITY_BILL => 'the utility bill was uploaded',
                self::TYPE_FIRE_REVIEW => 'the fire approval document was uploaded',
                self::TYPE_NTP_APPROVAL => 'the NTP Approval Date was filed as '.$project->ntp_approval_date,
                default => 'meter spot result "'.$project->meter_spot_result.'" was filled in',
            },
            ProjectDocumentFollowUp::REASON_PROJECT_ARCHIVED => 'the project was archived',
            default => match ($type) {
                self::TYPE_UTILITY_BILL => 'Utility Bill Uploaded is no longer No',
                self::TYPE_FIRE_REVIEW => 'Fire Review Required is no longer Yes',
                self::TYPE_NTP_APPROVAL => 'the NTP Approval Date is on file',
                default => 'MPU Required is no longer Yes',
            },
        };

        $this->record(
            $project,
            $type,
            'document_follow_up_cleared',
            $this->label($type).' cleared on '.$resolvedAt->format('d M Y, h:i A').' - '.$why.'.',
            [
                'document_follow_up_id' => $followUp->id,
                'resolved_at' => $resolvedAt->toDateTimeString(),
                'resolved_reason' => $reason,
            ],
            $causer
        );
    }

    /**
     * Some chases ask whether the paperwork is already in, not whether it is
     * needed ("Utility Bill Uploaded"). For those the files ARE the answer, so
     * the field follows them: one document on the project and it reads "yes";
     * the last one deleted and it reads "no" again, which re-opens the chase.
     * Leaving the field saying "no" next to a filed bill - or "yes" next to an
     * empty section - is simply wrong, and that field is what everyone reads on
     * the project page.
     *
     * The count is what matters, not which upload: deleting one of two bills
     * changes nothing, because documentReceived() still finds one.
     *
     * Going back to "no" is deliberately narrower than coming forward. It only
     * happens when the "yes" is one the documents themselves put there
     * (answeredByDocument()) - a project someone answered "yes" by hand, with
     * its bill on paper or filed among the ordinary department files, is never
     * contradicted, and neither is a "yes" typed to retract the question.
     *
     * Only ever writes when the field does not already say what the files say,
     * so it is safe to call on every sync.
     */
    public function syncFieldToDocuments(Project $project, string $type, ?User $causer = null): bool
    {
        $answer = $this->config($type)['answered_by_document'] ?? null;

        if (! $answer) {
            return false;
        }

        $hasDocument = $this->documentReceived($project, $type);

        if (! $hasDocument && ! $this->answeredByDocument($project, $type)) {
            return false;
        }

        $value = $hasDocument ? $answer['value'] : ($answer['value_when_missing'] ?? null);

        if ($value === null) {
            return false;
        }

        $column = $answer['column'];

        if (strtolower(trim((string) $project->{$column})) === strtolower($value)) {
            return false;
        }

        $project->update([$column => $value]);

        $this->record(
            $project,
            $type,
            'document_follow_up_field_answered',
            $answer['label'].' set to '.ucfirst($value).' automatically because '
                .($hasDocument ? $answer['because'] : $answer['because_missing']).'.',
            ['column' => $column, 'value' => $value],
            $causer
        );

        return true;
    }

    /**
     * The field's current answer is the one the document wrote: this chase's
     * last word on the project is that the document arrived. Anything else -
     * never chased, closed because the question was retracted, still open -
     * means the answer is somebody's own, and the files do not overrule it.
     */
    protected function answeredByDocument(Project $project, string $type): bool
    {
        $last = ProjectDocumentFollowUp::where('project_id', $project->id)
            ->ofType($type)
            ->latest('id')
            ->first();

        return $last
            && $last->status === ProjectDocumentFollowUp::STATUS_RESOLVED
            && $last->resolved_reason === ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED;
    }

    /* ------------------------------------------------------------- lane moves */

    /**
     * The one move each chase intercepts (Permitting -> Installation for MPU,
     * Inspection -> PTO for the utility bill). Returns the chase type whose
     * parked lane applies, or null when the move is an ordinary one.
     */
    public function forcedTypeForMove(Project $project, $targetDepartmentId): ?string
    {
        foreach (self::types() as $type) {
            if ($this->hasPending($project->id, $type)
                && (int) $project->department_id === (int) $this->fromDepartmentId($type)
                && (int) $targetDepartmentId === (int) $this->toDepartmentId($type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Open the chases that only start at the move they park. Called with the
     * move's target department BEFORE the move is written, so the interception
     * below sees a pending chase.
     *
     * Without this the NTP chase would have to open the moment a project has no
     * date - which is every project, from Deal Review on, chased for a date
     * nobody needs yet.
     */
    public function openChasesForMove(Project $project, $targetDepartmentId, ?User $causer = null): void
    {
        foreach (self::types() as $type) {
            if (empty($this->config($type)['opens_on_move'])) {
                continue;
            }

            if ((int) $project->department_id !== (int) $this->fromDepartmentId($type)
                || (int) $targetDepartmentId !== (int) $this->toDepartmentId($type)) {
                continue;
            }

            if ($this->hasPending($project->id, $type) || ! $this->needsFollowUp($project, $type)) {
                continue;
            }

            $this->open($project, $type, $causer);
        }
    }

    /** Note the forced lane on the project once the move has been written. */
    public function logForcedParkedLane(Project $project, string $type, $selectedSubDepartmentId, ?User $causer = null): void
    {
        $parkedId = $this->parkedSubDepartmentId($type);
        $parked = SubDepartment::find($parkedId);
        $selected = SubDepartment::find($selectedSubDepartmentId);
        $overridden = $selected && (int) $selectedSubDepartmentId !== $parkedId;

        $message = 'Moved to '.$this->config($type)['to_department'].' > '.($parked->name ?? 'Pending Document')
            .' because a '.$this->label($type).' is open'
            .($overridden ? ' (selected lane "'.$selected->name.'" was overridden)' : '')
            .'. No assignment e-mail was sent.';

        $this->record($project, $type, 'document_follow_up_lane_forced', $message, [
            'selected_sub_department_id' => $selectedSubDepartmentId,
            'forced_sub_department_id' => $parkedId,
        ], $causer);
    }

    /**
     * The document is in - a project parked in the chase's lane moves on to the
     * release lane, and this time the assignee is e-mailed.
     */
    public function releaseFromParkedLane(Project $project, string $type, ?User $causer = null): bool
    {
        $parkedId = $this->parkedSubDepartmentId($type);
        $releasedId = $this->releasedSubDepartmentId($type);

        if ((int) $project->department_id !== (int) $this->toDepartmentId($type)
            || (int) $project->sub_department_id !== $parkedId) {
            return false;
        }

        // Install Pending Document is the parked lane of two chases (the meter
        // spot result and the NTP approval date). Clearing one of them is not
        // permission to leave while the other is still owed - the project would
        // walk out of the lane that is holding it for the other document.
        if ($this->parkedByAnotherChase($project, $type, $parkedId)) {
            return false;
        }

        $project->update(['sub_department_id' => $releasedId]);

        $task = $this->currentTask($project);

        if ($task) {
            $task->update(['sub_department_id' => $releasedId]);
        }

        $this->record(
            $project,
            $type,
            'document_follow_up_lane_released',
            'Document received - project moved from '.(SubDepartment::find($parkedId)->name ?? 'the parked lane')
                .' to '.(SubDepartment::find($releasedId)->name ?? 'the next lane').'.',
            [
                'from_sub_department_id' => $parkedId,
                'to_sub_department_id' => $releasedId,
            ],
            $causer
        );

        if ($task) {
            $project->refresh();
            $this->assignmentService->notifyAssignedEmployee(
                Employee::with('user')->find($task->employee_id),
                $project,
                $task
            );
        }

        return true;
    }

    /**
     * A chase is open on this project and it is waiting for exactly this
     * project column - the NTP approval date being the one that matters.
     *
     * The zone tab that collects such a field has to stay writable even when
     * the project has moved past that zone, or the project is stuck: the field
     * is what releases it, and nobody else can file it.
     */
    public function isAwaitingColumn(Project $project, string $column): bool
    {
        foreach (self::types() as $type) {
            $config = $this->config($type);

            if (($config['value_column'] ?? null) === $column && $this->hasPending($project->id, $type)) {
                return true;
            }
        }

        return false;
    }

    /** Another chase, still open, parks projects in this same lane. */
    protected function parkedByAnotherChase(Project $project, string $type, int $parkedId): bool
    {
        foreach (self::types() as $other) {
            if ($other === $type) {
                continue;
            }

            if ((int) $this->parkedSubDepartmentId($other) === $parkedId
                && $this->hasPending($project->id, $other)) {
                return true;
            }
        }

        return false;
    }

    /* ---------------------------------------------------------------- helpers */

    protected function currentTask(Project $project): ?Task
    {
        return Task::where('project_id', $project->id)
            ->whereIn('status', ['In-Progress', 'Hold', 'Cancelled'])
            ->latest('id')
            ->first()
            ?? Task::where('project_id', $project->id)->latest('id')->first();
    }

    /**
     * Every step of a chase lands in both places the CRM keeps history: the
     * project activity log and the project's department notes.
     */
    protected function record(Project $project, string $type, string $event, string $message, array $properties = [], ?User $causer = null): void
    {
        $causer = $causer ?? auth()->user();
        $who = $causer->name ?? 'System';

        activity('project')
            ->performedOn($project)
            ->causedBy($causer)
            ->withProperties($properties + ['follow_up_type' => $type])
            ->setEvent($event)
            ->log($who.': '.$message);

        DepartmentNote::create([
            'project_id' => $project->id,
            'task_id' => $this->currentTask($project)?->id ?? 0,
            'department_id' => $project->department_id,
            'notes' => '['.$this->label($type).'] '.$message,
            'user_id' => $causer?->id,
        ]);
    }
}
