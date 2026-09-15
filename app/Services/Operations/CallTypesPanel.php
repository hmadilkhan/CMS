<?php

namespace App\Services\Operations;

use App\Models\Call;
use Illuminate\Http\Request;

class CallTypesPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.call-types.panel';
    }

    public function data(Request $request): array
    {
        return [
            'callTypes' => Call::all(),
            'callType' => $request->id != '' ? Call::where('id', $request->id)->first() : [],
        ];
    }
}
