<?php

namespace App\Services\Operations;

use App\Models\Department;
use App\Models\Tool;
use Illuminate\Http\Request;

class ToolsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'tools.panel';
    }

    public function data(Request $request): array
    {
        return [
            'tools' => Tool::with('department')->get(),
            'departments' => Department::all(),
            'tool' => $request->id != '' ? Tool::find($request->id) : [],
        ];
    }
}
