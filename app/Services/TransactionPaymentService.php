<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\ProjectInvoiceDetail;
use App\Models\TransactionPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Part payments: money received or paid against an account transaction or a
 * material invoice, one row per payment (transaction_payments).
 *
 * The parent keeps the summary every other screen already reads:
 *   settled_amount  the total received / paid so far (null when nothing yet)
 *   settled_date    the day of the last payment
 *   status          Closed once the total reaches the amount, or when a
 *                   payment is recorded as the last one (closed short);
 *                   Open otherwise, so a part-paid row stays on the board
 *                   with its balance.
 *
 * A row closed before payments were recorded one by one has a settled_amount
 * but no payment rows; it is shown from those two columns (see history()).
 */
class TransactionPaymentService
{
    /** Cent tolerance: a row paid to within half a cent of its amount is paid in full. */
    private const EPSILON = 0.005;

    /** What the row is for in full: a payment less its deduction, or a payable's amount. */
    public function fullAmount(AccountTransaction|ProjectInvoiceDetail $record): float
    {
        if ($record instanceof AccountTransaction && (float) $record->amount > 0) {
            return round((float) $record->amount - (float) $record->deduction_amount, 2);
        }

        return round(abs((float) $record->amount), 2);
    }

    /** Received / paid so far. */
    public function paid(AccountTransaction|ProjectInvoiceDetail $record): float
    {
        return round((float) $record->settled_amount, 2);
    }

    /** Still to come. Never below zero. */
    public function balance(AccountTransaction|ProjectInvoiceDetail $record): float
    {
        return max(0.0, round($this->fullAmount($record) - $this->paid($record), 2));
    }

    /**
     * Record money received / paid. The row closes when it is now paid in
     * full, or when `$final` (the rest is not coming); otherwise it stays Open
     * with the balance.
     */
    public function record(AccountTransaction|ProjectInvoiceDetail $record, float $amount, string $date, bool $final = false): TransactionPayment
    {
        $amount = round(abs($amount), 2);
        $date = Carbon::parse($date)->toDateString();

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter the amount received or paid.']);
        }

        return DB::transaction(function () use ($record, $amount, $date, $final) {
            $this->keepLegacySettlement($record);

            $payment = $record->payments()->create([
                'amount' => $amount,
                'paid_on' => $date,
                'created_by' => auth()->id(),
            ]);

            $this->sync($record, $final ? true : null);

            $this->log($record, 'payment', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'paid_on' => $date,
                'paid_so_far' => $this->paid($record),
                'balance' => $this->balance($record),
                'status' => $record->status,
            ], ($record->status === AccountTransaction::STATUS_CLOSED ? 'recorded the last payment of ' : 'recorded a part payment of ')
                .$this->money($amount).' on '.Carbon::parse($date)->format('m/d/Y')
                .' ('.$this->money($this->paid($record)).' of '.$this->money($this->fullAmount($record)).' paid)');

            return $payment;
        });
    }

    /** Close a row with nothing more to record (it was already paid, or is written off). */
    public function close(AccountTransaction|ProjectInvoiceDetail $record): void
    {
        $this->sync($record, true);
    }

    /** Undo one payment. The row reopens unless what is left still pays it in full. */
    public function remove(TransactionPayment $payment): void
    {
        $record = $payment->parent();

        DB::transaction(function () use ($payment, $record) {
            $payment->delete();

            if (! $record) {
                return;
            }

            $this->sync($record);

            $this->log($record, 'payment removed', [
                'payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'paid_on' => $payment->paid_on?->toDateString(),
                'paid_so_far' => $this->paid($record),
                'status' => $record->status,
            ], 'removed the payment of '.$this->money((float) $payment->amount).' made on '.$payment->paid_on?->format('m/d/Y'));
        });
    }

    /** Undo every payment: the row is Open with nothing received / paid (Reopen). */
    public function removeAll(AccountTransaction|ProjectInvoiceDetail $record): void
    {
        DB::transaction(function () use ($record) {
            $record->payments()->get()->each->delete();
            $this->sync($record, false);
        });
    }

    /**
     * Make what a closed row received / paid add up to `$total`, the last
     * payment on `$date` (the Account Transactions form edits the total, not
     * the payments one by one). Its last payment takes the difference; a row
     * with no payment rows gets one for the whole total.
     */
    public function setTotal(AccountTransaction|ProjectInvoiceDetail $record, float $total, string $date): void
    {
        $total = round(abs($total), 2);
        $date = Carbon::parse($date)->toDateString();

        DB::transaction(function () use ($record, $total, $date) {
            $payments = $record->payments()->get();

            if ($payments->isEmpty()) {
                if ($total > 0) {
                    $record->payments()->create(['amount' => $total, 'paid_on' => $date, 'created_by' => auth()->id()]);
                }
            } else {
                $last = $payments->last();
                $lastAmount = round($total - ((float) $payments->sum('amount') - (float) $last->amount), 2);

                if ($lastAmount <= 0) {
                    throw ValidationException::withMessages([
                        'settled_amount' => 'This was paid in '.$payments->count().' payments; the total cannot be less than the earlier ones ('
                            .$this->money((float) $payments->sum('amount') - (float) $last->amount).'). Change them from the Transaction Portal.',
                    ]);
                }

                $last->update(['amount' => $lastAmount, 'paid_on' => $date]);
            }

            $this->sync($record, true);
        });
    }

    /**
     * Every payment, oldest first, as the board shows it. A row closed before
     * payments were recorded one by one shows its one settled amount (it has
     * no id, so it cannot be removed on its own - Reopen does that).
     */
    public function history(AccountTransaction|ProjectInvoiceDetail $record): array
    {
        $payments = $record->relationLoaded('payments') ? $record->payments : $record->payments()->with('creator:id,name')->get();

        if ($payments->isEmpty()) {
            return $record->settled_date && (float) $record->settled_amount > 0
                ? [['id' => null, 'amount' => round((float) $record->settled_amount, 2), 'date' => Carbon::parse($record->settled_date)->toDateString(), 'by' => null]]
                : [];
        }

        return $payments->map(fn ($p) => [
            'id' => $p->id,
            'amount' => round((float) $p->amount, 2),
            'date' => $p->paid_on?->toDateString(),
            'by' => $p->creator?->name,
        ])->values()->all();
    }

    /**
     * Bring the parent in step with its payments. `$closed` null = closed when
     * paid in full; true / false = as given (closed short, or reopened).
     */
    public function sync(AccountTransaction|ProjectInvoiceDetail $record, ?bool $closed = null): void
    {
        $payments = $record->payments()->get(['amount', 'paid_on']);
        $paid = round((float) $payments->sum('amount'), 2);

        if ($closed === null) {
            $closed = $payments->isNotEmpty() && $paid >= $this->fullAmount($record) - self::EPSILON;
        }

        $record->forceFill([
            'status' => $closed ? AccountTransaction::STATUS_CLOSED : AccountTransaction::STATUS_OPEN,
            'settled_amount' => $payments->isEmpty() ? null : $paid,
            'settled_date' => $payments->isEmpty() ? null : $payments->max(fn ($p) => $p->paid_on->toDateString()),
        ])->save();

        $record->unsetRelation('payments');
    }

    /**
     * A row closed before payments were recorded one by one has its money in
     * settled_amount only. Before a first payment row is added, turn that into
     * a payment so the total keeps it.
     */
    private function keepLegacySettlement(AccountTransaction|ProjectInvoiceDetail $record): void
    {
        if ((float) $record->settled_amount > 0 && $record->settled_date && ! $record->payments()->exists()) {
            $record->payments()->create([
                'amount' => round((float) $record->settled_amount, 2),
                'paid_on' => Carbon::parse($record->settled_date)->toDateString(),
            ]);
        }
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }

    private function log(AccountTransaction|ProjectInvoiceDetail $record, string $event, array $properties, string $what): void
    {
        $user = auth()->user();
        $logName = $record instanceof ProjectInvoiceDetail ? 'ProjectInvoiceDetail' : 'AccountTransaction';
        $subject = $record instanceof ProjectInvoiceDetail ? 'material invoice' : 'account transaction';

        $activity = activity($logName)
            ->performedOn($record)
            ->withProperties($properties)
            ->setEvent($event);

        if ($user) {
            $activity->causedBy($user);
        }

        $activity->log(($user?->name ?? 'System')." {$what} on the {$subject}.");
    }
}
