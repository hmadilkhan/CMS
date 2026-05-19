<?php

namespace App\Services;

use App\Jobs\SendProjectAssignedEmailNotificationJob;
use App\Models\AssignDepartment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Support\Facades\Log;

class ProjectAssignmentService
{
    public function employeeForDepartment(int $departmentId): ?Employee
    {
        $assignedDepartment = AssignDepartment::with('employee.user')
            ->where('department_id', $departmentId)
            ->latest('id')
            ->first();

        if ($assignedDepartment?->employee && !$assignedDepartment->employee->trashed()) {
            return $assignedDepartment->employee;
        }

        $manager = Employee::with('user')
            ->whereHas('department', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->whereHas('user.roles', function ($query) {
                $query->where('roles.name', 'Manager');
            })
            ->first();

        if ($manager) {
            return $manager;
        }

        return Employee::with('user')
            ->whereHas('department', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->first();
    }

    public function notifyAssignedEmployee(?Employee $employee, Project $project, Task $task): void
    {
        if (!$employee?->user) {
            return;
        }

        $assignedBy = auth()->user()->name ?? 'System';
        $employee->user->notify(new ProjectAssignedNotification($project, $task, $assignedBy));

        if (empty($employee->user->email)) {
            return;
        }

        try {
            SendProjectAssignedEmailNotificationJob::dispatch($employee->user, $project, $task, $assignedBy)
                ->afterCommit();
        } catch (\Throwable $exception) {
            Log::error('Project assignment email notification queue failed.', [
                'project_id' => $project->id,
                'task_id' => $task->id,
                'employee_id' => $employee->id,
                'user_id' => $employee->user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
