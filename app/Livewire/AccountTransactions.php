<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AccountTransaction;
use App\Models\Project;

class AccountTransactions extends Component
{
    public $project_id;
    public $transactions;
    public $milestone;
    public $amount;
    public $transaction_date;
    public $transaction_details;
    public $transactionIdBeingEdited = null;
    public $isEditMode = false;
    public $confirmingDeleteId = null;

    protected $rules = [
        'milestone' => 'required|string|max:255',
        'amount' => 'required|numeric',
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
        $this->milestone = '';
        $this->amount = '';
        $this->transaction_date = '';
        $this->transaction_details = '';
        $this->transactionIdBeingEdited = null;
        $this->isEditMode = false;
    }

    public function save()
    {
        $this->validate();
        AccountTransaction::create([
            'project_id' => $this->project_id,
            'milestone' => $this->milestone,
            'amount' => $this->amount,
            'transaction_date' => $this->transaction_date,
            'transaction_details' => $this->transaction_details,
        ]);
        $this->resetForm();
        $this->fetchTransactions();
        session()->flash('message', 'Transaction added successfully.');
    }

    public function edit($id)
    {
        $transaction = AccountTransaction::findOrFail($id);
        $this->transactionIdBeingEdited = $id;
        $this->milestone = $transaction->milestone;
        $this->amount = $transaction->amount;
        $this->transaction_date = $transaction->transaction_date;
        $this->transaction_details = $transaction->transaction_details;
        $this->isEditMode = true;
    }

    public function update()
    {
        $this->validate();
        $transaction = AccountTransaction::findOrFail($this->transactionIdBeingEdited);
        $transaction->update([
            'milestone' => $this->milestone,
            'amount' => $this->amount,
            'transaction_date' => $this->transaction_date,
            'transaction_details' => $this->transaction_details,
        ]);
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
        AccountTransaction::findOrFail($id)->delete();
        $this->fetchTransactions();
        $this->confirmingDeleteId = null;
        session()->flash('message', 'Transaction deleted successfully.');
    }

    public function render()
    {
        return view('livewire.account-transactions');
    }
}
