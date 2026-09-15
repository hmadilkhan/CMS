<?php

namespace App\Services\Operations;

use App\Models\InverterType;
use Illuminate\Http\Request;

class InverterTypesPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.invertertype.panel';
    }

    public function data(Request $request): array
    {
        return [
            'inverterTypes' => InverterType::all(),
            'inverterType' => $request->id != '' ? InverterType::find($request->id) : [],
        ];
    }
}
