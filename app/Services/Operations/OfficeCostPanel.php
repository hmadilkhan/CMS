<?php

namespace App\Services\Operations;

use App\Models\OfficeCost;
use Illuminate\Http\Request;

class OfficeCostPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'office-costs.panel';
    }

    public function data(Request $request): array
    {
        return ['costs' => OfficeCost::all()];
    }
}
