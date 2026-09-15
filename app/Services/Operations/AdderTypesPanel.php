<?php

namespace App\Services\Operations;

use App\Models\AdderType;
use Illuminate\Http\Request;

class AdderTypesPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.adder-type.panel';
    }

    public function data(Request $request): array
    {
        return [
            'adders' => AdderType::all(),
            'adder' => $request->id != '' ? AdderType::where('id', $request->id)->first() : [],
        ];
    }
}
