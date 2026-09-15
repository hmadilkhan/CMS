<?php

namespace App\Services\Operations;

use App\Models\AssignDepartment;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class AssignDepartmentPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.assign-department.panel';
    }

    public function data(Request $request): array
    {
        return [
            'assignDepartments' => AssignDepartment::with('department', 'employee')->get(),
            'departments' => Department::all(),
            'employees' => Employee::all(),
            'assignDepartment' => $request->id ? AssignDepartment::with('department', 'employee')->findOrFail($request->id) : null,
        ];
    }
}
