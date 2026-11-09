<?php

namespace App\Services\Operations;

use App\Models\AccountTransaction;
use App\Models\FinanceOptionMilestone;
use App\Models\Payee;
use Illuminate\Http\Request;

/**
 * Operations -> Payees: the payee list behind every account transaction and
 * finance milestone. Built-ins are shown but locked; user-added payees can be
 * renamed, switched off, and deleted while nothing uses them.
 */
class PayeesPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.payees.panel';
    }

    public function data(Request $request): array
    {
        $payees = Payee::orderByDesc('is_system')->orderBy('label')->get();

        // How many rows store each key, deleted ones included (they still need the label).
        $transactions = AccountTransaction::withTrashed()->selectRaw('payee, count(*) as total')->groupBy('payee')->pluck('total', 'payee');
        $milestones = FinanceOptionMilestone::withTrashed()->selectRaw('payee, count(*) as total')->groupBy('payee')->pluck('total', 'payee');

        $payees->each(function (Payee $payee) use ($transactions, $milestones) {
            $payee->transactions_count = (int) ($transactions[$payee->key] ?? 0);
            $payee->milestones_count = (int) ($milestones[$payee->key] ?? 0);
        });

        $editing = $request->id != '' ? $payees->first(fn ($p) => $p->id == $request->id && ! $p->is_system) : null;

        return [
            'payees' => $payees,
            'payee' => $editing,
        ];
    }
}
