<?php

namespace App\Services\Operations;

use App\Models\LaborCost;
use Illuminate\Http\Request;

class LaborCostPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'labor-costs.panel';
    }

    public function data(Request $request): array
    {
        return ['costs' => LaborCost::all()];
    }
}
