<?php

namespace App\Services\Operations;

use App\Models\FinanceOption;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use Illuminate\Http\Request;

class DealerFeePanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.dealerfee.panel';
    }

    public function data(Request $request): array
    {
        return [
            'dealerfeelist' => LoanApr::with('loan', 'finance')->get(),
            'terms' => LoanTerm::groupBy('year')->orderBy('id', 'asc')->get(),
            'financing' => FinanceOption::all(),
            'loan' => $request->id != '' ? LoanApr::with('loan', 'loan.finance')->where('id', $request->id)->first() : [],
        ];
    }
}
