<?php

namespace App\Services\Operations;

use App\Models\FinanceOption;
use App\Models\LoanTerm;
use Illuminate\Http\Request;

class LoanTermsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.loan-term.panel';
    }

    public function data(Request $request): array
    {
        return [
            'financeOptions' => FinanceOption::all(),
            'loanTerms' => LoanTerm::with('finance')->get(),
            'loanTerm' => $request->id != '' ? LoanTerm::where('id', $request->id)->first() : [],
        ];
    }
}
