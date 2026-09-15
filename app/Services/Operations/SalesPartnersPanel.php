<?php

namespace App\Services\Operations;

use App\Models\SalesPartner;
use Illuminate\Http\Request;

class SalesPartnersPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.sales-partner.panel';
    }

    public function data(Request $request): array
    {
        return [
            'partners' => SalesPartner::all(),
            'partner' => $request->id != '' ? SalesPartner::where('id', $request->id)->first() : [],
        ];
    }
}
