<?php

namespace App\Services\Operations;

use App\Models\UtilityCompany;
use Illuminate\Http\Request;

class UtilityCompanyPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.utility-company.panel';
    }

    public function data(Request $request): array
    {
        return [
            'utilityCompanies' => UtilityCompany::all(),
            'utility' => $request->id != '' ? UtilityCompany::where('id', $request->id)->first() : [],
        ];
    }
}
