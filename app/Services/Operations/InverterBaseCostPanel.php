<?php

namespace App\Services\Operations;

use App\Models\InverterType;
use App\Models\InverterTypeRate;
use Illuminate\Http\Request;

class InverterBaseCostPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.redline.panel';
    }

    public function data(Request $request): array
    {
        return [
            'redlinelist' => InverterTypeRate::with('inverter')->get(),
            'inverters' => InverterType::all(),
            'redline' => $request->id != '' ? InverterTypeRate::find($request->id) : [],
        ];
    }
}
