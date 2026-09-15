<?php

namespace App\Services\Operations;

use App\Models\InverterType;
use App\Models\ModuleType;
use Illuminate\Http\Request;

class ModuleTypesPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'module-types.panel';
    }

    /**
     * The record to edit comes from `?id=`, the way the console asks for it.
     * The screen's own edit route carries it in the path instead, so its
     * controller hands the id over here - see ModuleTypeController::edit.
     */
    public function data(Request $request): array
    {
        return [
            'types' => ModuleType::with('inverter')->latest()->get(),
            'inverterTypes' => InverterType::orderBy('name')->get(),
            'type' => $request->id != '' ? ModuleType::find($request->id) : [],
        ];
    }
}
