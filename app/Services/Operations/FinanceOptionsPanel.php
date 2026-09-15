<?php

namespace App\Services\Operations;

use App\Models\FinanceMilestoneEmailRecipient;
use App\Models\FinanceMilestoneSetting;
use App\Models\FinanceOption;
use App\Services\FinanceMilestoneService;
use Illuminate\Http\Request;

class FinanceOptionsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.finance-options.panel';
    }

    public function data(Request $request): array
    {
        return [
            'financeOptions' => FinanceOption::with('milestones')->get(),
            'finance' => $request->id != '' ? FinanceOption::with('milestones')->where('id', $request->id)->first() : [],
            'milestoneEmailRecipients' => FinanceMilestoneEmailRecipient::orderBy('mode')->orderBy('email')->get(),
            'milestoneEmailMode' => FinanceMilestoneSetting::where('key', 'email_mode')->value('value') ?: FinanceMilestoneService::MODE_TEST,
        ];
    }
}
