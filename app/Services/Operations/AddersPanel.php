<?php

namespace App\Services\Operations;

use App\Models\Adder;
use App\Models\AdderType;
use App\Models\AdderUnit;
use Illuminate\Http\Request;

class AddersPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.adders.panel';
    }

    public function data(Request $request): array
    {
        return [
            'adders' => Adder::with('type', 'unit')->get(),
            'types' => AdderType::all(),
            'units' => AdderUnit::all(),
            'adder' => $request->id != '' ? Adder::with('type', 'unit')->where('id', $request->id)->first() : [],
        ];
    }
}
