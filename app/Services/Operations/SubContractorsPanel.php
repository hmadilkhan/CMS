<?php

namespace App\Services\Operations;

use App\Models\SubContractor;
use Illuminate\Http\Request;

class SubContractorsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.sub-contractor.panel';
    }

    public function data(Request $request): array
    {
        return [
            'contractors' => SubContractor::all(),
            'contractor' => $request->id != '' ? SubContractor::where('id', $request->id)->first() : [],
        ];
    }
}
