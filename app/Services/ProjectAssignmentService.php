<?php

namespace App\Services;

use App\Models\AssignDepartment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\ProjectAssignedNotification;

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
    }
}
