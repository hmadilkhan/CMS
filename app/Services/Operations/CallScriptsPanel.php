<?php

namespace App\Services\Operations;

use App\Models\Call;
use App\Models\CallScript;
use App\Models\Department;
use Illuminate\Http\Request;

class CallScriptsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.call-scripts.panel';
    }

    public function data(Request $request): array
    {
        return [
            'calls' => Call::all(),
            'departments' => Department::all(),
            'callScripts' => CallScript::with('call', 'department')->get(),
            'script' => $request->id != '' ? CallScript::with('call', 'department')->where('id', $request->id)->first() : [],
        ];
    }
}
