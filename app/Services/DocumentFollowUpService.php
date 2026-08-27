<?php

namespace App\Services;

use App\Models\AssignDepartment;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocumentFollowUp;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;

/**
 * "Document Follow Up" - the MPU paperwork chase.
 *
 * A project whose MPU Required is "yes" but whose meter spot result is still
 * empty sits on the Engineering assignee's dashboard until the result comes in.
 * While it sits there:
 *   - moving it from Permitting into Installation forces the Install Pending
 *     Document sub lane, whatever lane the user picked on the front end;
 *   - the newly assigned employee gets no e-mail for that move;
 * and once the meter spot result is filled the follow up closes, and a project
 * parked in Install Pending Document moves on to Install Not Scheduled - that
 * move does e-mail the assignee.
 */
class DocumentFollowUpService
{
    /** Whose dashboard the list belongs to. */
    public const OWNER_DEPARTMENT = 'Engineering';

    /** The lane a project must be leaving for the forced sub lane to apply. */
    public const FROM_DEPARTMENT = 'Permitting';

    /** The lane it is moving into. */
    public const TO_DEPARTMENT = 'Installation';

    /** A project parked here is out of the chase. */
    public const ARCHIVED_DEPARTMENT = 'Archived';

    /** Installation > Install Pending Document. */
    public const PENDING_DOCUMENT_SUB_DEPARTMENT_ID = 31;

    /** Installation > Install Not Scheduled. */
    public const NOT_SCHEDULED_SUB_DEPARTMENT_ID = 12;

    protected ProjectAssignmentService $assignmentService;

    public function __construct(ProjectAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /* ---------------------------------------------------------------- lookups */

    public function ownerDepartmentId(): ?int
    {
        return Department::where('name', self::OWNER_DEPARTMENT)->value('id');
    }

    public function fromDepartmentId(): ?int
    {
        return Department::where('name', self::FROM_DEPARTMENT)->value('id');
    }

    public function toDepartmentId(): ?int
    {
        return Department::where('name', self::TO_DEPARTMENT)->value('id');
    }

    /**
     * Only the Engineering people listed in Operations > Assign Department get
     * the section.
     */
    public function visibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $ownerDepartmentId = $this->ownerDepartmentId();

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

    public function archivedDepartmentId(): ?int
    {
        return Department::where('name', self::ARCHIVED_DEPARTMENT)->value('id');
    }

    /**
     * The live list, newest chase first. Reconciles first so projects whose MPU
     * Required was set before this feature existed - or through a path that
     * does not call sync() - still show up.
     */
    public function pendingList()
    {
        $this->syncAll();

        return ProjectDocumentFollowUp::with(['project.customer', 'project.department', 'project.subdepartment'])
            ->pending()
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($followUp) => $followUp->project !== null)
            ->values();
    }

    public function pendingFor(int $projectId): ?ProjectDocumentFollowUp
    {
        return ProjectDocumentFollowUp::where('project_id', $projectId)->pending()->latest('id')->first();
    }

    public function hasPending(int $projectId): bool
    {
        return ProjectDocumentFollowUp::where('project_id', $projectId)->pending()->exists();
    }

    /** MPU is required and the meter spot result has not come back yet. */
    public function needsFollowUp(Project $project): bool
    {
        return strtolower((string) $project->mpu_required) === 'yes'
            && trim((string) $project->meter_spot_result) === ''
            && (int) $project->department_id !== (int) $this->archivedDepartmentId();
    }

    /* ------------------------------------------------------------ reconciling */

    /**
     * Bring the follow up in step with the project: open one when MPU turns
     * "yes", close it when the meter spot result lands (or MPU stops being
     * required). Safe to call after any project write.
     */
    public function sync(Project $project, ?User $causer = null): void
    {
        $pending = $this->pendingFor($project->id);

        if ($this->needsFollowUp($project)) {
            if (! $pending) {
                $this->open($project, $causer);
            }

            return;
        }

        if (! $pending) {
            return;
        }

        if (trim((string) $project->meter_spot_result) !== '') {
            $reason = ProjectDocumentFollowUp::REASON_METER_SPOT_RESULT;
        } elseif ((int) $project->department_id === (int) $this->archivedDepartmentId()) {
            $reason = ProjectDocumentFollowUp::REASON_PROJECT_ARCHIVED;
        } else {
            $reason = ProjectDocumentFollowUp::REASON_MPU_NOT_REQUIRED;
        }

        $this->resolve($pending, $project, $reason, $causer);

        if ($reason === ProjectDocumentFollowUp::REASON_METER_SPOT_RESULT) {
            $this->releaseFromPendingDocumentLane($project, $causer);
        }
    }

    /**
     * Reconcile every project that either belongs on the list or is on it now.
     * Cheap - the candidate set is only the MPU projects still missing a result
     * plus whatever is already open.
     */
    public function syncAll(?User $causer = null): void
    {
        $candidateIds = Project::query()
            ->whereRaw('lower(mpu_required) = ?', ['yes'])
            ->where(function ($query) {
                $query->whereNull('meter_spot_result')->orWhere('meter_spot_result', '');
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

    /** Put the project on the Engineering dashboard list. */
    public function open(Project $project, ?User $causer = null): ProjectDocumentFollowUp
    {
        $employee = $this->assignmentService->employeeForDepartment((int) $this->ownerDepartmentId());

        $followUp = ProjectDocumentFollowUp::create([
            'project_id' => $project->id,
            'employee_id' => $employee?->id,
            'department_id' => $project->department_id,
            'sub_department_id' => $project->sub_department_id,
            'status' => ProjectDocumentFollowUp::STATUS_PENDING,
            'opened_at' => now(),
        ]);

        $this->record(
            $project,
            'document_follow_up_opened',
            'Document Follow Up opened: MPU Required is Yes and the meter spot result is still missing.',
            ['document_follow_up_id' => $followUp->id],
            $causer
        );

        return $followUp;
    }

    /** Take it off the list, keeping the date and time it left (and why). */
    public function resolve(ProjectDocumentFollowUp $followUp, Project $project, string $reason, ?User $causer = null): void
    {
        $resolvedAt = now();

        $followUp->update([
            'status' => ProjectDocumentFollowUp::STATUS_RESOLVED,
            'resolved_at' => $resolvedAt,
            'resolved_reason' => $reason,
            'resolved_by' => $causer?->id ?? auth()->id(),
        ]);

        $why = match ($reason) {
            ProjectDocumentFollowUp::REASON_METER_SPOT_RESULT => 'meter spot result "'.$project->meter_spot_result.'" was filled in',
            ProjectDocumentFollowUp::REASON_PROJECT_ARCHIVED => 'the project was archived',
            default => 'MPU Required is no longer Yes',
        };

        $this->record(
            $project,
            'document_follow_up_cleared',
            'Document Follow Up cleared on '.$resolvedAt->format('d M Y, h:i A').' - '.$why.'.',
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
     * Permitting -> Installation while the follow up is open: the project goes
     * into Install Pending Document no matter which lane was selected.
     */
    public function shouldForcePendingDocumentLane(Project $project, $targetDepartmentId): bool
    {
        return $this->hasPending($project->id)
            && (int) $project->department_id === (int) $this->fromDepartmentId()
            && (int) $targetDepartmentId === (int) $this->toDepartmentId();
    }

    /** Note the forced lane on the project once the move has been written. */
    public function logForcedPendingDocumentLane(Project $project, $selectedSubDepartmentId, ?User $causer = null): void
    {
        $selected = SubDepartment::find($selectedSubDepartmentId);
        $overridden = $selected && (int) $selectedSubDepartmentId !== self::PENDING_DOCUMENT_SUB_DEPARTMENT_ID;

        $message = 'Moved to Installation > Install Pending Document because a Document Follow Up is open'
            .($overridden ? ' (selected lane "'.$selected->name.'" was overridden)' : '')
            .'. No assignment e-mail was sent.';

        $this->record($project, 'document_follow_up_lane_forced', $message, [
            'selected_sub_department_id' => $selectedSubDepartmentId,
            'forced_sub_department_id' => self::PENDING_DOCUMENT_SUB_DEPARTMENT_ID,
        ], $causer);
    }

    /**
     * The result is in - a project parked in Install Pending Document moves on
     * to Install Not Scheduled, and this time the assignee is e-mailed.
     */
    public function releaseFromPendingDocumentLane(Project $project, ?User $causer = null): bool
    {
        if ((int) $project->department_id !== (int) $this->toDepartmentId()
            || (int) $project->sub_department_id !== self::PENDING_DOCUMENT_SUB_DEPARTMENT_ID) {
            return false;
        }

        $project->update(['sub_department_id' => self::NOT_SCHEDULED_SUB_DEPARTMENT_ID]);

        $task = $this->currentTask($project);

        if ($task) {
            $task->update(['sub_department_id' => self::NOT_SCHEDULED_SUB_DEPARTMENT_ID]);
        }

        $this->record(
            $project,
            'document_follow_up_lane_released',
            'Meter spot result received - project moved from Install Pending Document to Install Not Scheduled.',
            [
                'from_sub_department_id' => self::PENDING_DOCUMENT_SUB_DEPARTMENT_ID,
                'to_sub_department_id' => self::NOT_SCHEDULED_SUB_DEPARTMENT_ID,
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
     * Every step of the chase lands in both places the CRM keeps history: the
     * project activity log and the project's department notes.
     */
    protected function record(Project $project, string $event, string $message, array $properties = [], ?User $causer = null): void
    {
        $causer = $causer ?? auth()->user();
        $who = $causer->name ?? 'System';

        activity('project')
            ->performedOn($project)
            ->causedBy($causer)
            ->withProperties($properties)
            ->setEvent($event)
            ->log($who.': '.$message);

        DepartmentNote::create([
            'project_id' => $project->id,
            'task_id' => $this->currentTask($project)?->id ?? 0,
            'department_id' => $project->department_id,
            'notes' => '[Document Follow Up] '.$message,
            'user_id' => $causer?->id,
        ]);
    }
}
