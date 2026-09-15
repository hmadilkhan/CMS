<?php

namespace App\Services\Operations;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.departments.panel';
    }

    public function data(Request $request): array
    {
        return [
            'departments' => Department::all(),
            'department' => $request->id != '' ? Department::where('id', $request->id)->first() : [],
        ];
    }
}
