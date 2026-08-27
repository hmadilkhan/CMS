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
 *   Utility Bill  - Deal Review owns it. Opens when Utility Bill Required is
 *                   "yes" and no bill has been uploaded; clears when the bill
 *                   itself is uploaded from the follow up card.
 *
 * While a chase is open the project may still travel the pipeline normally,
 * but the one move that would carry it past the paperwork is intercepted: it
 * lands in that chase's parked lane instead of the lane the user picked, and
 * that single move sends no assignment e-mail. The parked lane is closed
 * (sub_departments.show_in_move_list = 0), so nothing can be moved out of it
 * by hand. Producing the missing document closes the chase, moves the project
 * on to the release lane, and e-mails the assignee. Answering the department
 * field "no" - the paperwork is not needed after all - closes it too, and the
 * project is released just the same so it is never stranded in a closed lane.
 */
class DocumentFollowUpService
{
    public const TYPE_MPU = 'mpu';

    public const TYPE_UTILITY_BILL = 'utility_bill';

    /** A project parked here is out of every chase. */
    public const ARCHIVED_DEPARTMENT = 'Archived';

    /**
     * Everything that differs between the two chases. Sub-department ids are
     * fixed records: 31 Install Pending Document, 12 Install Not Scheduled,
     * 32 PTO Pending Document, 18 PTO.
     */
    public const TYPES = [
        self::TYPE_MPU => [
            'label' => 'Document Follow Up',
            'owner_department' => 'Engineering',
            'from_department' => 'Permitting',
            'to_department' => 'Installation',
            'parked_sub_department_id' => 31,
            'released_sub_department_id' => 12,
        ],
        self::TYPE_UTILITY_BILL => [
            'label' => 'Utility Bill Follow Up',
            'owner_department' => 'Deal Review',
            'from_department' => 'Inspection',
            'to_department' => 'PTO',
            'parked_sub_department_id' => 32,
            'released_sub_department_id' => 18,
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

    /** The document is still missing and the project is still in play. */
    public function needsFollowUp(Project $project, string $type): bool
    {
        if ((int) $project->department_id === (int) $this->archivedDepartmentId()) {
            return false;
        }

        return match ($type) {
            self::TYPE_UTILITY_BILL => strtolower((string) $project->utility_bill_required) === 'yes'
                && ! $this->documentReceived($project, $type),
            default => strtolower((string) $project->mpu_required) === 'yes'
                && trim((string) $project->meter_spot_result) === '',
        };
    }

    /**
     * The missing document has arrived. For the utility bill that is the bill
     * itself - a file marked as a utility bill, which only the follow up card
     * uploads - not an answer on a dropdown.
     */
    public function documentReceived(Project $project, string $type): bool
    {
        return match ($type) {
            self::TYPE_UTILITY_BILL => ProjectFile::where('project_id', $project->id)
                ->category(ProjectFile::CATEGORY_UTILITY_BILL)
                ->exists(),
            default => trim((string) $project->meter_spot_result) !== '',
        };
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
        $pending = $this->pendingFor($project->id, $type);

        if ($this->needsFollowUp($project, $type)) {
            if (! $pending) {
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
                })->orWhereRaw('lower(utility_bill_required) = ?', ['yes']);
            })
            ->pluck('id')
            ->merge(ProjectDocumentFollowUp::pending()->pluck('project_id'))
            ->unique();

        if ($candidateIds->isEmpty()) {
            return;
        }

        foreach (Project::whereIn('id', $candidateIds)->get() as $project) {
            $this->sync($project, $causer);
        }
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

        $why = $type === self::TYPE_UTILITY_BILL
            ? 'Utility Bill Required is Yes and the bill has not been uploaded yet'
            : 'MPU Required is Yes and the meter spot result is still missing';

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
            ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED => $type === self::TYPE_UTILITY_BILL
                ? 'the utility bill was uploaded'
                : 'meter spot result "'.$project->meter_spot_result.'" was filled in',
            ProjectDocumentFollowUp::REASON_PROJECT_ARCHIVED => 'the project was archived',
            default => $type === self::TYPE_UTILITY_BILL
                ? 'Utility Bill Required is no longer Yes'
                : 'MPU Required is no longer Yes',
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
