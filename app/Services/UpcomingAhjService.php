<?php

namespace App\Services;

use App\Models\AssignDepartment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAhjTrack;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

class UpcomingAhjService
{
    /** Departments a project must currently be in to count as an upcoming AHJ. */
    public const UPCOMING_DEPARTMENTS = ['Site Survey', 'Engineering'];

    /** The department whose assigned users get the Upcoming AHJ's tab. */
    public const OWNER_DEPARTMENT = 'Permitting';

    /**
     * Admin sees the tab too; everyone else needs a Permitting assignment
     * in the Operations > Assign Department table.
     */
    public function visibleTo(User $user): bool
    {
        if ($user->hasRole('Admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        $employeeIds = Employee::where('user_id', $user->id)->pluck('id');

        if ($employeeIds->isEmpty()) {
            return false;
        }

        return AssignDepartment::whereIn('employee_id', $employeeIds)
            ->whereIn('department_id', Department::where('name', self::OWNER_DEPARTMENT)->pluck('id'))
            ->exists();
    }

    /** Ids of the lanes an upcoming AHJ project sits in. */
    public function laneDepartmentIds()
    {
        return Department::whereIn('name', self::UPCOMING_DEPARTMENTS)->pluck('id');
    }

    public function permittingDepartmentId()
    {
        return Department::where('name', self::OWNER_DEPARTMENT)->value('id');
    }

    /**
     * Keep the tracking table in step with reality:
     *  - start tracking any project that is in the lanes now
     *  - stamp the exit on anything tracked that has since left the lanes
     *
     * Projects are only tracked from the moment they are first seen here, so
     * nothing that had already moved on gets back-filled.
     */
    public function sync(): void
    {
        $laneIds = $this->laneDepartmentIds();
        $currentProjects = Project::whereIn('department_id', $laneIds)->get(['id', 'department_id']);
        $tracked = ProjectAhjTrack::whereIn('project_id', $currentProjects->pluck('id'))->get()->keyBy('project_id');

        foreach ($currentProjects as $project) {
            $track = $tracked->get($project->id);

            if (! $track) {
                ProjectAhjTrack::create([
                    'project_id' => $project->id,
                    'department_id' => $project->department_id,
                    'first_seen_at' => now(),
                ]);

                continue;
            }

            if ($track->department_id != $project->department_id) {
                $track->update(['department_id' => $project->department_id]);
            }
        }

        $this->stampProjectsThatLeftTheLanes($laneIds, $currentProjects->pluck('id'));
    }

    /**
     * Anything still tracked as "in the list" but no longer in Site Survey /
     * Engineering has left the lane - record when, and where it went.
     */
    protected function stampProjectsThatLeftTheLanes($laneIds, $currentProjectIds): void
    {
        $permittingId = $this->permittingDepartmentId();

        $gone = ProjectAhjTrack::with('project')
            ->whereNull('removed_at')
            ->whereNotIn('project_id', $currentProjectIds)
            ->get();

        foreach ($gone as $track) {
            if (! $track->project) {
                $track->delete();

                continue;
            }

            $newDepartmentId = $track->project->department_id;

            $track->update([
                'removed_at' => $this->departmentEntryTime($track->project_id, $newDepartmentId),
                'removed_reason' => $newDepartmentId == $permittingId
                    ? ProjectAhjTrack::REASON_MOVED_TO_PERMITTING
                    : ProjectAhjTrack::REASON_MOVED_OUT,
                'moved_to_department_id' => $newDepartmentId,
            ]);
        }
    }

    /**
     * When the project entered its new lane. Every move path creates a task in
     * the target department, so that task's created_at is the real move time;
     * fall back to now() if the history is missing.
     */
    protected function departmentEntryTime($projectId, $departmentId): Carbon
    {
        $enteredAt = Task::where('project_id', $projectId)
            ->where('department_id', $departmentId)
            ->orderByDesc('id')
            ->value('created_at');

        return $enteredAt ? Carbon::parse($enteredAt) : now();
    }

    /**
     * The live list: projects in Site Survey / Engineering that nobody has
     * removed by hand.
     */
    public function projectsFor(User $user)
    {
        $this->sync();

        $removedProjectIds = ProjectAhjTrack::whereNotNull('removed_at')->pluck('project_id')->all();

        return Project::with('department')
            ->whereIn('department_id', $this->laneDepartmentIds())
            ->whereNotIn('id', $removedProjectIds)
            ->orderBy('code')
            ->get(['id', 'code', 'project_name', 'ahj', 'department_id']);
    }

    /** History of everything that has left the list, newest first. */
    public function removedProjects()
    {
        return ProjectAhjTrack::with(['project:id,code,project_name,ahj', 'removedBy:id,name', 'movedToDepartment:id,name'])
            ->whereNotNull('removed_at')
            ->orderByDesc('removed_at')
            ->get()
            ->filter(fn ($track) => $track->project !== null)
            ->values();
    }

    /** "Mark As Remove" - hides the project for everyone and records who/when. */
    public function markRemoved(Project $project, User $user): ProjectAhjTrack
    {
        $track = ProjectAhjTrack::firstOrNew(['project_id' => $project->id]);

        $track->fill([
            'department_id' => $track->department_id ?? $project->department_id,
            'first_seen_at' => $track->first_seen_at ?? now(),
            'removed_at' => now(),
            'removed_reason' => ProjectAhjTrack::REASON_MANUAL,
            'removed_by' => $user->id,
            'moved_to_department_id' => null,
        ])->save();

        return $track;
    }

    /**
     * Undo a manual removal. A project that left because it moved on cannot be
     * restored - it is simply no longer in the lanes.
     */
    public function restore(Project $project): bool
    {
        $track = ProjectAhjTrack::where('project_id', $project->id)->first();

        if (! $track || ! $track->isManual()) {
            return false;
        }

        $track->update([
            'removed_at' => null,
            'removed_reason' => null,
            'removed_by' => null,
        ]);

        return true;
    }
}
