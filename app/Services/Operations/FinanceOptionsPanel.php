<?php

namespace App\Services\Operations;

use App\Models\AccountTransaction;
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
        $milestones = app(FinanceMilestoneService::class);
        $finance = $request->id != '' ? FinanceOption::with('milestones')->where('id', $request->id)->first() : null;

        return [
            'financeOptions' => FinanceOption::with('milestones')->get(),
            'finance' => $finance ?? [],
            'milestoneEmailRecipients' => FinanceMilestoneEmailRecipient::orderBy('mode')->orderBy('email')->get(),
            'milestoneEmailMode' => FinanceMilestoneSetting::where('key', 'email_mode')->value('value') ?: FinanceMilestoneService::MODE_TEST,
            // The schedule editor: a failed save reopens with what was posted.
            'milestoneRows' => array_values((array) (old('milestones') ?? $milestones->editorRows($finance))),
            'milestoneTriggers' => $milestones->triggerOptions(),
            'milestoneAmountTypes' => FinanceMilestoneService::AMOUNT_TYPES,
            'milestoneBases' => config('finance_milestones.amount_bases', []),
            'milestonePayees' => AccountTransaction::payeeOptions(),
        ];
    }
}
