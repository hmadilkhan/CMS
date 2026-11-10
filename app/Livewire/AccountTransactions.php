<?php

namespace App\Livewire;

use App\Models\AccountTransaction;
use App\Models\Project;
use App\Services\CommissionPayableService;
use App\Services\TransactionPaymentService;
use Illuminate\Support\Facades\DB;
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
    // What closing records: the TOTAL received / paid and the day of the last payment.
    public $settled_amount;
    public $settled_date;
    public $originalStatus = null;
    // The payee the row being edited already has - still offered if it was switched off since.
    public $originalPayee = null;
    // Received / paid so far on the row being edited (part payments from the Transaction Portal).
    public $paidSoFar = 0;
    public $paymentCount = 0;
    public $transactionIdBeingEdited = null;
    public $isEditMode = false;
    public $confirmingDeleteId = null;

    protected function rules()
    {
        return [
            'payee' => ['required', Rule::in(array_keys(AccountTransaction::payeeChoices($this->originalPayee)))],
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

    private function payments(): TransactionPaymentService
    {
        return app(TransactionPaymentService::class);
    }

    /**
     * Bring the row's payments in step with the form, after its own columns
     * are saved. `$before` is the row as it was (settled_amount, settled_date,
     * full amount).
     *
     *  - Set to Open from Closed: its payments are undone, as the portal's
     *    Reopen does. An Open row keeps its part payments.
     *  - Closed now: the box is the TOTAL received / paid; what earlier part
     *    payments did not cover is recorded as one more payment.
     *  - Already closed: an edit to the total or the date is saved (the last
     *    payment takes the difference). Before, an edit to a closed row kept
     *    the old figures and the Transaction Portal went on showing them.
     */
    private function applySettlement(AccountTransaction $transaction, array $before): void
    {
        $payments = $this->payments();

        if ($this->status !== AccountTransaction::STATUS_CLOSED) {
            if ($this->isEditMode && $this->originalStatus === AccountTransaction::STATUS_CLOSED) {
                $payments->removeAll($transaction);
            }

            return;
        }

        if ($this->closingNow()) {
            $new = round(abs((float) $this->settled_amount) - $payments->paid($transaction), 2);
            $new > 0 ? $payments->record($transaction, $new, $this->settled_date, true) : $payments->close($transaction);

            return;
        }

        [$total, $date] = $this->editedSettlement($before);

        if ($total !== null) {
            $payments->setTotal($transaction, $total, $date);
        }
    }

    /**
     * The total and date to save on a row that was already closed, or
     * [null, null] for no change. Left blank, they are kept: a row closed
     * before settlement existed has neither. A row closed at its full amount
     * whose amount is changed, with the received box left as it was, follows
     * the new amount; a part payment keeps its received amount.
     */
    private function editedSettlement(array $before): array
    {
        $stored = $before['settled_amount'] === null ? null : round((float) $before['settled_amount'], 2);
        $storedDate = $before['settled_date'] ? substr((string) $before['settled_date'], 0, 10) : null;

        $total = filled($this->settled_amount) ? round(abs((float) $this->settled_amount), 2) : $stored;
        $date = filled($this->settled_date) ? substr((string) $this->settled_date, 0, 10) : $storedDate;

        if ($total !== null && $total === $stored && $stored === $before['full']) {
            $total = round(abs((float) $this->amount - (float) $this->deduction_amount), 2);
        }

        if ($total === null || $date === null || $total <= 0 || ($total === $stored && $date === $storedDate)) {
            return [null, null];
        }

        return [$total, $date];
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
        $this->originalPayee = null;
        $this->paidSoFar = 0;
        $this->paymentCount = 0;
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
        ];
        $closedNow = $this->closingNow();

        $account = DB::transaction(function () use ($items) {
            $account = AccountTransaction::create($items);
            $this->applySettlement($account, ['settled_amount' => null, 'settled_date' => null, 'full' => null]);

            return $account;
        });

        $username = auth()->user()->name;
        $logged = $items + $account->fresh()->only(['settled_amount', 'settled_date']);
        // Get the changed field names
        $changedFields = collect($logged)->keys()->implode(', ');
        activity('AccountTransaction')
            ->performedOn($account)
            ->causedBy(auth()->user()) // Log who did the action
            ->withProperties($logged)
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
        $this->originalPayee = $transaction->payee;
        $this->milestone = $transaction->milestone;
        $this->amount = $transaction->amount;
        $this->deduction_amount = $transaction->deduction_amount;
        $this->transaction_date = $transaction->transaction_date;
        $this->transaction_details = $transaction->transaction_details;
        $this->status = $transaction->status ?: AccountTransaction::STATUS_OPEN;
        $this->originalStatus = $this->status;
        // The boxes hold a closed row's total. An open row's are left empty, so
        // closing it offers the full amount - not what part payments made so far.
        $closed = $this->status === AccountTransaction::STATUS_CLOSED;
        $this->settled_amount = $closed ? $transaction->settled_amount : null;
        $this->settled_date = $closed ? $transaction->settled_date : null;
        $this->paidSoFar = round((float) $transaction->settled_amount, 2);
        $this->paymentCount = $transaction->payments()->count();
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
        ];
        $closedNow = $this->closingNow();
        $before = [
            'settled_amount' => $transaction->settled_amount,
            'settled_date' => $transaction->settled_date,
            'full' => $this->payments()->fullAmount($transaction),
        ];

        // One transaction: a total the payments cannot take is refused and nothing is saved.
        DB::transaction(function () use ($transaction, $items, $before) {
            $transaction->update($items);
            $this->applySettlement($transaction, $before);
        });

        // ADDING TO LOGS
        $username = auth()->user()->name;
        $logged = $items + $transaction->fresh()->only(['settled_amount', 'settled_date']);
        // Get the changed field names
        $changedFields = collect($logged)->keys()->implode(', ');
        activity('AccountTransaction')
            ->performedOn($transaction)
            ->causedBy(auth()->user()) // Log who did the action
            ->withProperties($logged)
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
