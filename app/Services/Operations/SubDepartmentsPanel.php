<?php

namespace App\Services\Operations;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;

class SubDepartmentsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.sub-departments.panel';
    }

    public function data(Request $request): array
    {
        return [
            'departments' => Department::all(),
            'subDepartments' => SubDepartment::with('department')->orderBy('department_id')->orderBy('order')->get(),
            'subDepartment' => $request->id != '' ? SubDepartment::with('department')->where('id', $request->id)->first() : [],
        ];
    }
}
