<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The Transaction Portal's data: five sections, read for one period (a week or
 * a month) plus every row from before it that is still open.
 *
 *   ear  Expected AR  open transactions with a positive amount (money coming in)
 *   aar  Actual AR    those same transactions once closed, by the date received
 *   com  Commission   negative transactions paid to the Sales Partner
 *   lab  Labor        negative transactions paid to the Sub-Contractor
 *   mat  Material     Material invoices (project_invoice_details)
 *
 * Expected AR is behind its own permission (the Funding Manager's section); the
 * other four share one. Closing a row needs the permission of its section.
 */
class TransactionPortalService
{
    public const PERMISSION_PAGE = 'View Transaction Portal';

    public const PERMISSION_EXPECTED = 'Transaction Portal Expected AR';

    public const PERMISSION_OTHER = 'Transaction Portal Other Sections';

    /** How a period reads its payables (see rows()). */
    private const ALL = 'all';

    private const PAID_ONLY = 'paid_only';

    private const PAY_RUN = 'pay_run';

    private const RELATIONS = [
        'project.customer.finances.finance',
        'project.customer.salespartner',
        'project.customer.subContractor',
    ];

    public function __construct(private CommissionPayableService $commissions) {}

    /** The section keys `$user` may see, in board order. */
    public function sections(User $user): array
    {
        $sections = [];

        if ($user->can(self::PERMISSION_EXPECTED)) {
            $sections[] = 'ear';
        }

        if ($user->can(self::PERMISSION_OTHER)) {
            array_push($sections, 'aar', 'com', 'lab', 'mat');
        }

        return $sections;
    }

    /**
     * Every row the board shows for the period, flat, each tagged with its
     * section (`b`). Rows from before `$start` come only while still open.
     *
     * A period that is already over (its last day is before today) is closed:
     * its payables (Commission, Labor, Material) show only what was paid in it.
     * An unpaid payable is carried into the current period instead, so it is
     * not listed again under every week it has been outstanding. Expected AR
     * is not affected.
     *
     * A week with a pay run (payables picked to be paid on a day in it) shows,
     * of its open payables, only the picked ones; the rest move to the next
     * week, where they sit under "Earlier, still open". Paid rows show as
     * always. A month is never narrowed this way.
     */
    public function rows(User $user, Carbon $start, Carbon $end): array
    {
        $sections = $this->sections($user);
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $mode = match (true) {
            $this->isClosedPeriod($end) => self::PAID_ONLY,
            $this->isWeek($start, $end) && $this->hasPayRun($user, $start, $end) => self::PAY_RUN,
            default => self::ALL,
        };
        $rows = [];

        if (in_array('ear', $sections, true)) {
            $rows[] = $this->transactions($user)
                ->where('amount', '>', 0)
                ->where('status', AccountTransaction::STATUS_OPEN)
                ->whereDate('transaction_date', '<=', $end)
                ->get()
                ->map(fn ($t) => $this->expectedRow($t));
        }

        if (in_array('aar', $sections, true)) {
            $rows[] = $this->transactions($user)
                ->where('amount', '>', 0)
                ->where('status', AccountTransaction::STATUS_CLOSED)
                ->whereBetween('settled_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->map(fn ($t) => $this->actualRow($t));
        }

        if (in_array('com', $sections, true)) {
            $rows[] = $this->payables($user, 'sales_partner', 'COALESCE(due_date, transaction_date)', $start, $end, $mode)
                ->get()
                ->map(fn ($t) => $this->commissionRow($t));
        }

        if (in_array('lab', $sections, true)) {
            $rows[] = $this->payables($user, 'sub_contractor', 'transaction_date', $start, $end, $mode)
                ->with('invoice')
                ->get()
                ->map(fn ($t) => $this->laborRow($t));
        }

        if (in_array('mat', $sections, true)) {
            $rows[] = $this->materialInvoices($start, $end, $mode)->map(fn ($i) => $this->materialRow($i));
        }

        return collect($rows)->flatten(1)->values()->all();
    }

    /** A week or month whose last day is before today: its payables show only what was paid in it. */
    public function isClosedPeriod(Carbon $end): bool
    {
        return $end->copy()->startOfDay()->lt(today());
    }

    /** One portal week (seven days from the configured first day), not a month. */
    public function isWeek(Carbon $start, Carbon $end): bool
    {
        return $start->dayOfWeek === (int) config('transaction_portal.week_starts_on', Carbon::SUNDAY)
            && (int) $start->diffInDays($end) === 6;
    }

    /** Whether any payable the user can see is in a pay run on a day of this period. */
    public function hasPayRun(User $user, Carbon $start, Carbon $end): bool
    {
        $period = [$start->toDateString(), $end->toDateString()];

        $transactions = $this->transactions($user)
            ->where('amount', '<', 0)
            ->whereIn('payee', ['sales_partner', 'sub_contractor'])
            ->whereBetween('pay_run_date', $period)
            ->exists();

        return $transactions || ProjectInvoiceDetail::query()
            ->where('invoice_type', 'material')
            ->whereHas('project', fn ($q) => $q->whereNull('projects.deleted_at'))
            ->whereBetween('pay_run_date', $period)
            ->exists();
    }

    /**
     * Put an open payable in the pay run for `$date`, or take it out (null).
     * A paid one keeps its pay run, which is what it was paid under.
     */
    public function setPayRun(AccountTransaction|ProjectInvoiceDetail $record, ?string $date): bool
    {
        if ($record->status !== AccountTransaction::STATUS_OPEN) {
            return false;
        }

        $date = $date ? Carbon::parse($date)->toDateString() : null;
        $record->update(['pay_run_date' => $date]);

        $user = auth()->user();
        $what = $record instanceof ProjectInvoiceDetail ? 'material invoice' : 'account transaction';

        activity($record instanceof ProjectInvoiceDetail ? 'ProjectInvoiceDetail' : 'AccountTransaction')
            ->performedOn($record)
            ->causedBy($user)
            ->withProperties(['pay_run_date' => $date])
            ->setEvent('updated')
            ->log(($user?->name ?? 'System').($date
                ? " put the {$what} in the ".Carbon::parse($date)->format('m/d/Y').' pay run from the Transaction Portal.'
                : " took the {$what} out of the pay run from the Transaction Portal."));

        return true;
    }

    /** Which section a transaction belongs to, or null when the portal does not show it. */
    public function sectionOf(AccountTransaction $transaction): ?string
    {
        if ((float) $transaction->amount > 0) {
            return $transaction->status === AccountTransaction::STATUS_OPEN ? 'ear' : 'aar';
        }

        return match ($transaction->payee) {
            'sales_partner' => 'com',
            'sub_contractor' => 'lab',
            default => null,
        };
    }

    public function permissionFor(string $section): string
    {
        return $section === 'ear' ? self::PERMISSION_EXPECTED : self::PERMISSION_OTHER;
    }

    /**
     * Close a transaction with what was actually received or paid. A customer
     * payment (positive) then moves to Actual AR and may raise the commission.
     *
     * @return AccountTransaction|null the commission payable this payment raised
     */
    public function settleTransaction(AccountTransaction $transaction, float $amount, string $date): ?AccountTransaction
    {
        $date = Carbon::parse($date)->toDateString();

        DB::transaction(function () use ($transaction, $amount, $date) {
            $transaction->update([
                'status' => AccountTransaction::STATUS_CLOSED,
                'settled_amount' => round(abs($amount), 2),
                'settled_date' => $date,
            ]);

            $this->log($transaction, 'closed', [
                'settled_amount' => round(abs($amount), 2),
                'settled_date' => $date,
            ]);
        });

        if ((float) $transaction->amount > 0 && $transaction->project) {
            return $this->commissions->raiseForPayment($transaction->project, $date);
        }

        return null;
    }

    public function reopenTransaction(AccountTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => AccountTransaction::STATUS_OPEN,
                'settled_amount' => null,
                'settled_date' => null,
            ]);

            $this->log($transaction, 'reopened', []);
        });
    }

    public function settleInvoice(ProjectInvoiceDetail $invoice, float $amount, string $date): void
    {
        $invoice->update([
            'status' => ProjectInvoiceDetail::STATUS_CLOSED,
            'settled_amount' => round(abs($amount), 2),
            'settled_date' => Carbon::parse($date)->toDateString(),
        ]);

        $this->log($invoice, 'paid', ['settled_amount' => round(abs($amount), 2), 'settled_date' => $date], 'ProjectInvoiceDetail');
    }

    public function reopenInvoice(ProjectInvoiceDetail $invoice): void
    {
        $invoice->update([
            'status' => ProjectInvoiceDetail::STATUS_OPEN,
            'settled_amount' => null,
            'settled_date' => null,
        ]);

        $this->log($invoice, 'reopened', [], 'ProjectInvoiceDetail');
    }

    /** Transactions on live projects, scoped the way Account Transactions already is. */
    private function transactions(User $user): Builder
    {
        return AccountTransaction::query()
            ->filterByRole($user)
            ->whereHas('project', fn ($q) => $q->whereNull('projects.deleted_at'))
            ->with(self::RELATIONS);
    }

    /**
     * Money owed out to `$payee`. An open row sits in the period of `$keyDate`
     * (and is carried forward while still open); a paid row sits in the period
     * it was PAID in. A row closed before the portal existed has no paid date,
     * so it stays on `$keyDate`.
     *
     * Open rows by `$mode`: ALL = due in the period or earlier; PAY_RUN = only
     * those in a pay run on a day of the period; PAID_ONLY = none.
     */
    private function payables(User $user, string $payee, string $keyDate, Carbon $start, Carbon $end, string $mode = self::ALL): Builder
    {
        $period = [$start->toDateString(), $end->toDateString()];

        return $this->transactions($user)
            ->where('payee', $payee)
            ->where('amount', '<', 0)
            ->where(function ($q) use ($keyDate, $end, $period, $mode) {
                $q->where(function ($q) use ($keyDate, $period) {
                    $q->where('status', AccountTransaction::STATUS_CLOSED)
                        ->whereRaw("COALESCE(settled_date, $keyDate) between ? and ?", $period);
                });

                if ($mode === self::ALL) {
                    $q->orWhere(function ($q) use ($keyDate, $end) {
                        $q->where('status', AccountTransaction::STATUS_OPEN)
                            ->whereRaw("$keyDate <= ?", [$end->toDateString()]);
                    });
                } elseif ($mode === self::PAY_RUN) {
                    $q->orWhere(function ($q) use ($period) {
                        $q->where('status', AccountTransaction::STATUS_OPEN)
                            ->whereBetween('pay_run_date', $period);
                    });
                }
            });
    }

    /**
     * Material invoices. Open: due (invoice date + N days) in the period, or
     * earlier. Paid: paid in the period (an invoice paid before the portal
     * existed has no paid date and stays on its due date). Open ones follow
     * `$mode` the way payables() does.
     */
    private function materialInvoices(Carbon $start, Carbon $end, string $mode = self::ALL)
    {
        $days = $this->materialDueDays();
        $period = [$start->toDateString(), $end->toDateString()];
        $invoicedFor = [
            $start->copy()->subDays($days)->toDateString(),
            $end->copy()->subDays($days)->toDateString(),
        ];

        return ProjectInvoiceDetail::query()
            ->where('invoice_type', 'material')
            ->whereHas('project', fn ($q) => $q->whereNull('projects.deleted_at'))
            ->with(self::RELATIONS)
            ->where(function ($q) use ($period, $invoicedFor, $mode) {
                $q->where(function ($q) use ($period) {
                    $q->where('status', ProjectInvoiceDetail::STATUS_CLOSED)
                        ->whereBetween('settled_date', $period);
                })->orWhere(function ($q) use ($invoicedFor) {
                    $q->where('status', ProjectInvoiceDetail::STATUS_CLOSED)
                        ->whereNull('settled_date')
                        ->whereBetween('invoice_date', $invoicedFor);
                });

                if ($mode === self::ALL) {
                    $q->orWhere(function ($q) use ($invoicedFor) {
                        $q->where('status', ProjectInvoiceDetail::STATUS_OPEN)
                            ->whereDate('invoice_date', '<=', $invoicedFor[1]);
                    });
                } elseif ($mode === self::PAY_RUN) {
                    $q->orWhere(function ($q) use ($period) {
                        $q->where('status', ProjectInvoiceDetail::STATUS_OPEN)
                            ->whereBetween('pay_run_date', $period);
                    });
                }
            })
            ->get();
    }

    private function expectedRow(AccountTransaction $t): array
    {
        return $this->projectFields($t->project) + [
            'id' => 't'.$t->id,
            'b' => 'ear',
            'milestone' => $t->milestone,
            'expected' => $this->date($t->transaction_date),
            'amount' => round((float) $t->amount - (float) $t->deduction_amount, 2),
            'status' => $t->status,
        ];
    }

    private function actualRow(AccountTransaction $t): array
    {
        return $this->projectFields($t->project) + [
            'id' => 't'.$t->id,
            'b' => 'aar',
            'milestone' => $t->milestone,
            'received' => $this->date($t->settled_date),
            'amount' => (float) $t->settled_amount,
            'status' => $t->status,
        ];
    }

    private function commissionRow(AccountTransaction $t): array
    {
        return $this->projectFields($t->project) + [
            'id' => 't'.$t->id,
            'b' => 'com',
            'milestone' => $t->milestone,
            'paymentReceived' => $this->date($t->transaction_date),
            'due' => $this->date($t->due_date),
            'amount' => abs((float) $t->amount),
            'status' => $t->status,
            'paidOn' => $this->date($t->settled_date),
            'payRun' => $this->date($t->pay_run_date),
        ];
    }

    private function laborRow(AccountTransaction $t): array
    {
        $invoice = $t->invoice;

        return $this->projectFields($t->project) + [
            'id' => 't'.$t->id,
            'b' => 'lab',
            'milestone' => $t->milestone,
            'inspection' => $this->date($t->transaction_date),
            'due' => null,
            'amount' => abs((float) $t->amount),
            'status' => $t->status,
            'paidOn' => $this->date($t->settled_date),
            'payRun' => $this->date($t->pay_run_date),
            'invoiceNo' => $invoice ? app(SubContractorPayableService::class)->invoiceNumber($invoice) : null,
            'file' => $invoice?->original_file_name,
        ];
    }

    private function materialRow(ProjectInvoiceDetail $i): array
    {
        return $this->projectFields($i->project) + [
            'id' => 'i'.$i->id,
            'b' => 'mat',
            'invoiceDate' => $this->date($i->invoice_date),
            'due' => $this->date(Carbon::parse($i->invoice_date)->addDays($this->materialDueDays())),
            'amount' => (float) $i->amount,
            'status' => $i->status,
            'paidOn' => $this->date($i->settled_date),
            'payRun' => $this->date($i->pay_run_date),
            'file' => $i->original_file_name,
        ];
    }

    private function projectFields(?Project $project): array
    {
        $customer = $project?->customer;

        return [
            'projectId' => $project?->id,
            'project' => $project?->project_name ?? 'Unknown project',
            'projectUrl' => $project ? route('projects.show', $project->id) : null,
            'finance' => $customer?->finances?->finance?->name,
            'partner' => $customer?->salespartner?->name,
            'sub' => $customer?->subContractor?->name,
        ];
    }

    private function materialDueDays(): int
    {
        return (int) config('transaction_portal.material_due_days', 30);
    }

    private function date($value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    private function log($subject, string $event, array $properties, string $logName = 'AccountTransaction'): void
    {
        $user = auth()->user();
        $what = $logName === 'AccountTransaction' ? 'account transaction' : 'material invoice';

        activity($logName)
            ->performedOn($subject)
            ->causedBy($user)
            ->withProperties($properties)
            ->setEvent('updated')
            ->log(($user?->name ?? 'System')." {$event} the {$what} from the Transaction Portal.");
    }
}
