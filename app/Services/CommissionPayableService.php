<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Raises the sales partner's Commission Payable when a customer payment is
 * received (an Expected AR transaction is closed).
 *
 * The commission is what is left of the money received once the Labor and
 * Material invoices are paid for, counted over the whole project so no invoice
 * is deducted twice:
 *
 *   commission now = everything received so far
 *                  - Labor invoices - Material invoices
 *                  - commission already raised
 *
 * Nothing is raised until both invoice types are on file, or when the result
 * is not above zero; the next payment received works it out again. It is due
 * on the Friday of the week after the payment arrived.
 */
class CommissionPayableService
{
    public function raiseForPayment(Project $project, string $receivedDate): ?AccountTransaction
    {
        try {
            $project->loadMissing('customer.salespartner');
            $partner = $project->customer?->salespartner;

            if (! $partner) {
                Log::info('Commission Payable not created: the project has no sales partner.', [
                    'project_id' => $project->id,
                ]);

                return null;
            }

            $invoiceTypes = $project->invoiceDetails()
                ->whereIn('invoice_type', array_keys(ProjectInvoiceDetail::REQUIRED_TYPES))
                ->distinct()
                ->pluck('invoice_type');

            if ($invoiceTypes->count() < count(ProjectInvoiceDetail::REQUIRED_TYPES)) {
                Log::info('Commission Payable not created yet: the Labor and Material invoices are not both on file.', [
                    'project_id' => $project->id,
                ]);

                return null;
            }

            return DB::transaction(function () use ($project, $partner, $receivedDate) {
                // Two payments closed at once must not both see "nothing raised yet".
                Project::whereKey($project->id)->lockForUpdate()->first();

                $amount = $this->outstandingCommission($project);

                if ($amount <= 0) {
                    return null;
                }

                $items = [
                    'project_id' => $project->id,
                    'payee' => 'sales_partner',
                    'milestone' => 'Commission',
                    'amount' => -$amount,
                    'deduction_amount' => 0,
                    'transaction_date' => Carbon::parse($receivedDate)->toDateString(),
                    'due_date' => self::dueDate($receivedDate)->toDateString(),
                    'transaction_details' => 'Commission Payable - '.$partner->name,
                    'status' => AccountTransaction::STATUS_OPEN,
                ];

                $transaction = AccountTransaction::create($items);

                $activity = activity('AccountTransaction')
                    ->performedOn($transaction)
                    ->withProperties($items)
                    ->setEvent('created');

                if (auth()->check()) {
                    $activity->causedBy(auth()->user());
                }

                $activity->log($this->actor().' created the account transaction for the payment received: Commission Payable.');

                return $transaction;
            });
        } catch (\Throwable $throwable) {
            // The payment has already been recorded; a failure here must not undo that.
            Log::error('Commission Payable creation failed', [
                'project_id' => $project->id ?? null,
                'error' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    /** Received so far, less both invoice types and the commission already raised. */
    public function outstandingCommission(Project $project): float
    {
        $received = (float) AccountTransaction::where('project_id', $project->id)
            ->where('amount', '>', 0)
            ->where('status', AccountTransaction::STATUS_CLOSED)
            ->sum('settled_amount');

        $invoices = (float) $project->invoiceDetails()
            ->whereIn('invoice_type', array_keys(ProjectInvoiceDetail::REQUIRED_TYPES))
            ->sum('amount');

        // Hand-entered commission rows count too, so the portal never pays it twice.
        $raised = abs((float) AccountTransaction::where('project_id', $project->id)
            ->where('payee', 'sales_partner')
            ->where('amount', '<', 0)
            ->sum('amount'));

        return round($received - $invoices - $raised, 2);
    }

    /** The Friday of the week after the one `$date` falls in. */
    public static function dueDate(Carbon|string $date): Carbon
    {
        $weekStart = (int) config('transaction_portal.week_starts_on', Carbon::SUNDAY);

        return Carbon::parse($date)
            ->startOfWeek($weekStart)
            ->addWeek()
            ->addDays((Carbon::FRIDAY - $weekStart + 7) % 7);
    }

    private function actor(): string
    {
        return auth()->check() ? auth()->user()->name : 'System';
    }
}
