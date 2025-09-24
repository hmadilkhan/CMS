<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AccountTransaction;
use App\Models\Project;

class AccountTransactions extends Component
{
    public $project_id;
    public $payee;
    public $transactions;
    public $milestone;
    public $amount;
    public $deduction_amount = 0;
    public $transaction_date;
    public $transaction_details;
    public $transactionIdBeingEdited = null;
    public $isEditMode = false;
    public $confirmingDeleteId = null;

    protected $rules = [
        'payee' => 'required|string|max:255',
        'milestone' => 'required|string|max:255',
        'amount' => 'required|numeric',
        'deduction_amount' => 'required|numeric',
        'transaction_date' => 'required|date',
        'transaction_details' => 'required|string',
    ];

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
        ];
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
        ];
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
