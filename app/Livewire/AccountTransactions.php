<?php

namespace App\Livewire;

use App\Models\AccountTransaction;
use App\Models\Project;
use App\Services\CommissionPayableService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AccountTransactions extends Component
{
    public $project_id;
    public $payee;
    public $transactions;
    public $milestone;
    public $amount;
    public $deduction_amount;
    public $transaction_date;
    public $transaction_details;
    public $status = AccountTransaction::STATUS_OPEN;
    // What closing records: the amount received / paid and the day it was.
    public $settled_amount;
    public $settled_date;
    public $originalStatus = null;
    public $transactionIdBeingEdited = null;
    public $isEditMode = false;
    public $confirmingDeleteId = null;

    protected function rules()
    {
        return [
            'payee' => ['required', Rule::in(array_keys(AccountTransaction::payeeOptions()))],
            'milestone' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'deduction_amount' => 'required|numeric',
            'transaction_date' => 'required|date',
            'transaction_details' => 'required|string',
            'status' => 'required|in:Open,Closed',
            'settled_amount' => [Rule::requiredIf(fn () => $this->closingNow()), 'nullable', 'numeric', 'min:0'],
            'settled_date' => [Rule::requiredIf(fn () => $this->closingNow()), 'nullable', 'date'],
        ];
    }

    /** Being closed by this save (a row that was already closed is not asked again). */
    private function closingNow(): bool
    {
        return $this->status === AccountTransaction::STATUS_CLOSED
            && (! $this->isEditMode || $this->originalStatus !== AccountTransaction::STATUS_CLOSED);
    }

    public function updatedStatus($value)
    {
        if ($this->closingNow() && blank($this->settled_date)) {
            $this->settled_amount = round(abs((float) $this->amount - (float) $this->deduction_amount), 2) ?: null;
            $this->settled_date = now()->toDateString();
        }
    }

    /** The settlement columns this save writes: kept on a closed row, cleared on an open one. */
    private function settlementItems(): array
    {
        if ($this->status !== AccountTransaction::STATUS_CLOSED) {
            return ['settled_amount' => null, 'settled_date' => null];
        }

        if (! $this->closingNow()) {
            return [];
        }

        return [
            'settled_amount' => round(abs((float) $this->settled_amount), 2),
            'settled_date' => $this->settled_date,
        ];
    }

    /** A customer payment received raises the sales partner's commission (see CommissionPayableService). */
    private function raiseCommission(bool $closedNow): void
    {
        if ($closedNow && (float) $this->amount > 0 && $project = Project::find($this->project_id)) {
            app(CommissionPayableService::class)->raiseForPayment($project, $this->settled_date);
        }
    }

    public function mount($project_id = null)
    {
        $this->project_id = $project_id;
        $this->fetchTransactions();
    }

    public function fetchTransactions()
    {
        $this->transactions = AccountTransaction::where('project_id', $this->project_id)->orderBy('transaction_date', 'desc')->get();
    }

    public function resetForm()
    {
        $this->payee = '';
        $this->milestone = '';
        $this->amount = '';
        $this->deduction_amount = '';
        $this->transaction_date = '';
        $this->transaction_details = '';
        $this->status = AccountTransaction::STATUS_OPEN;
        $this->settled_amount = null;
        $this->settled_date = null;
        $this->originalStatus = null;
        $this->transactionIdBeingEdited = null;
        $this->isEditMode = false;
    }

    public function save()
    {
        $this->validate();
        $items = [
            'project_id' => $this->project_id,
            'payee' => $this->payee,
            'milestone' => $this->milestone,
            'amount' => $this->amount,
            'deduction_amount' => $this->deduction_amount,
            'transaction_date' => $this->transaction_date,
            'transaction_details' => $this->transaction_details,
            'status' => $this->status,
        ] + $this->settlementItems();
        $closedNow = $this->closingNow();

        $account = AccountTransaction::create($items);

        $username = auth()->user()->name;
        // Get the changed field names
        $changedFields = collect($items)->keys()->implode(', ');
        activity('AccountTransaction')
            ->performedOn($account)
            ->causedBy(auth()->user()) // Log who did the action
            ->withProperties($items)
            ->setEvent("created")
            ->log("{$username} created the account transaction: {$changedFields}.");

        $this->raiseCommission($closedNow);
        $this->resetForm();
        $this->fetchTransactions();
        session()->flash('message', 'Transaction added successfully.');
    }

    public function edit($id)
    {
        $transaction = AccountTransaction::findOrFail($id);
        $this->transactionIdBeingEdited = $id;
        $this->payee = $transaction->payee;
        $this->milestone = $transaction->milestone;
        $this->amount = $transaction->amount;
        $this->deduction_amount = $transaction->deduction_amount;
        $this->transaction_date = $transaction->transaction_date;
        $this->transaction_details = $transaction->transaction_details;
        $this->status = $transaction->status ?: AccountTransaction::STATUS_OPEN;
        $this->originalStatus = $this->status;
        $this->settled_amount = $transaction->settled_amount;
        $this->settled_date = $transaction->settled_date;
        $this->isEditMode = true;
    }

    public function update()
    {
        $this->validate();
        $transaction = AccountTransaction::findOrFail($this->transactionIdBeingEdited);
        $items = [
            'payee' => $this->payee,
            'milestone' => $this->milestone,
            'amount' => $this->amount,
            'deduction_amount' => $this->deduction_amount,
            'transaction_date' => $this->transaction_date,
            'transaction_details' => $this->transaction_details,
            'status' => $this->status,
        ] + $this->settlementItems();
        $closedNow = $this->closingNow();
        $transaction->update($items);
        // ADDING TO LOGS
        $username = auth()->user()->name;
        // Get the changed field names
        $changedFields = collect($items)->keys()->implode(', ');
        activity('AccountTransaction')
            ->performedOn($transaction)
            ->causedBy(auth()->user()) // Log who did the action
            ->withProperties($items)
            ->setEvent("updated")
            ->log("{$username} updated the account transaction: {$changedFields}.");
        $this->raiseCommission($closedNow);
        $this->resetForm();
        $this->fetchTransactions();
        session()->flash('message', 'Transaction updated successfully.');
    }

    public function confirmDelete($id)
    {
        $this->confirmingDeleteId = $id;
    }

    public function delete($id)
    {
        $account = AccountTransaction::findOrFail($id);
        AccountTransaction::findOrFail($id)->delete();
        $username = auth()->user()->name;
        $changedFields = collect($account)->keys()->implode(', ');
        activity('AccountTransaction')
            ->performedOn($account)
            ->causedBy(auth()->user()) // Log who did the action
            ->withProperties([])
            ->setEvent("deleted")
            ->log("{$username} deleted the account transaction: {$changedFields}.");
        $this->fetchTransactions();
        $this->confirmingDeleteId = null;
        session()->flash('message', 'Transaction deleted successfully.');
    }

    public function render()
    {
        return view('livewire.account-transactions');
    }
}
