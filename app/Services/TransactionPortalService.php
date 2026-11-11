<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use App\Models\TransactionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The Transaction Portal's data: five sections, read for one period (a week or
 * a month) plus every row from before it that is still open.
 *
 *   ear  Expected AR  open transactions with a positive amount (money coming in)
 *   aar  Actual AR    money received on them, one item per payment (part
 *                     payments too), by the date received
 *   com  Commission   negative transactions paid to the Sales Partner
 *   lab  Labor        negative transactions paid to the Sub-Contractor
 *   mat  Material     Material invoices (project_invoice_details)
 *   oth  Other        negative transactions paid to anyone else (Supplier,
 *                     Others, Customer, a payee added by a user...)
 *
 * Expected AR is behind its own permission (the Funding Manager's section); the
 * others share one. Closing a row needs the permission of its section.
 */
class TransactionPortalService
{
    /** Payees with a section of their own; every other payee's payables are "Other". */
    public const OWN_SECTION_PAYEES = ['sales_partner', 'sub_contractor'];

    /** The money-out sections: a pay run, a tick box and Reopen apply to these. */
    public const PAYABLE_SECTIONS = ['com', 'lab', 'mat', 'oth'];

    public const PERMISSION_PAGE = 'View Transaction Portal';

    public const PERMISSION_EXPECTED = 'Transaction Portal Expected AR';

    public const PERMISSION_OTHER = 'Transaction Portal Other Sections';

    /** Deleting a received payment also needs the Account Transactions edit permission. */
    public const PERMISSION_DELETE = 'Account Transactions Edit';

    /** Adding a transaction from the portal: the project page's Add button needs the same. */
    public const PERMISSION_CREATE = 'Account Transactions Edit';

    /** Adding a material invoice: the project's Invoices tab needs the same. */
    public const PERMISSION_INVOICE = 'Invoice Details';

    /** How a period reads its payables (see rows()). */
    private const ALL = 'all';

    private const PAID_ONLY = 'paid_only';

    /** What a transaction with no project is called on the board. */
    public const MISC_LABEL = 'Miscellaneous payment';

    /** The most rows an all-time search returns. */
    public const SEARCH_LIMIT = 200;

    private const RELATIONS = [
        'project.customer.finances.finance',
        'project.customer.salespartner',
        'project.customer.subContractor',
        'payments.creator:id,name',
    ];

    public function __construct(
        private CommissionPayableService $commissions,
        private TransactionPaymentService $payments,
    ) {}

    /** The section keys `$user` may see, in board order. */
    public function sections(User $user): array
    {
        $sections = [];

        if ($user->can(self::PERMISSION_EXPECTED)) {
            $sections[] = 'ear';
        }

        if ($user->can(self::PERMISSION_OTHER)) {
            array_push($sections, 'aar', 'com', 'lab', 'mat', 'oth');
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
     * A batch (payables picked to be paid on a day, `pay_run_date`) hides and
     * moves nothing: every lane shows what it always shows, and the page's
     * Batch filter narrows the payable lanes to the picked rows. An open row
     * batched for a day in the period is listed in it even when it falls due
     * later, so the filter finds it.
     */
    public function rows(User $user, Carbon $start, Carbon $end): array
    {
        $sections = $this->sections($user);
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $mode = match (true) {
            $this->isClosedPeriod($end) => self::PAID_ONLY,
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
            $period = [$start->toDateString(), $end->toDateString()];

            // Every payment received in the period, part payments included...
            $received = $this->receivedPayments($user)
                ->whereBetween('paid_on', $period)
                ->get()
                ->map(fn ($p) => $this->actualRow($p->transaction, $p));

            // ...and rows closed before payments were recorded one by one.
            $legacy = $this->transactions($user)
                ->where('amount', '>', 0)
                ->where('status', AccountTransaction::STATUS_CLOSED)
                ->whereBetween('settled_date', $period)
                ->whereDoesntHave('payments')
                ->get()
                ->map(fn ($t) => $this->actualRow($t));

            $rows[] = $this->withReceivedHints($received->concat($legacy));
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

        if (in_array('oth', $sections, true)) {
            $rows[] = $this->payables($user, null, 'COALESCE(due_date, transaction_date)', $start, $end, $mode)
                ->get()
                ->map(fn ($t) => $this->otherRow($t));
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

    /**
     * Put an open payable in the batch for `$date` (the day it is to be paid),
     * or take it out (null). A paid one keeps its batch, which is what it was
     * paid under. Stored as `pay_run_date` (the batch was called a pay run).
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
                ? " put the {$what} in the ".Carbon::parse($date)->format('m/d/Y').' batch from the Transaction Portal.'
                : " took the {$what} out of the batch from the Transaction Portal."));

        return true;
    }

    /**
     * Search every period at once: rows of any date and status, in the
     * sections `$user` may see, scoped exactly like the board. Matches the
     * project (name, code, customer), sales partner, sub-contractor, finance
     * option, milestone, details, payee, invoice file - and, when the search
     * is a number, the amount. Newest first, at most SEARCH_LIMIT rows.
     *
     * @return array{rows: array, truncated: bool}
     */
    public function search(User $user, string $q): array
    {
        $sections = $this->sections($user);
        $canExpected = in_array('ear', $sections, true);
        $canOther = in_array('aar', $sections, true);
        $q = trim($q);

        if ((! $canExpected && ! $canOther) || mb_strlen($q) < 2) {
            return ['rows' => [], 'truncated' => false];
        }

        $like = '%'.addcslashes($q, '%_\\').'%';
        $number = preg_replace('/[$,\s]/', '', $q);
        $amount = is_numeric($number) ? round((float) $number, 2) : null;
        $payees = collect(AccountTransaction::payeeOptions())
            ->filter(fn ($label) => mb_stripos((string) $label, $q) !== false)
            ->keys()
            ->all();

        $transactions = $this->transactions($user)
            ->with('invoice')
            ->whereNotNull('transaction_date')
            ->where(function ($w) use ($canExpected, $canOther) {
                if ($canExpected) {
                    $w->orWhere(fn ($q) => $q->where('amount', '>', 0)->where('status', AccountTransaction::STATUS_OPEN));
                }
                if ($canOther) {
                    // Actual AR: payments received (part payments on an open row too),
                    // or a row closed with a received date before those were recorded.
                    $w->orWhere(fn ($q) => $q->where('amount', '>', 0)->where(function ($q) {
                        $q->whereHas('payments')->orWhere(fn ($q) => $q
                            ->where('status', AccountTransaction::STATUS_CLOSED)
                            ->whereNotNull('settled_date'));
                    }));
                    $w->orWhere('amount', '<', 0);
                }
            })
            ->where(function ($w) use ($like, $payees, $amount, $q) {
                $w->where('milestone', 'like', $like)
                    ->orWhere('transaction_details', 'like', $like)
                    ->orWhereHas('project', fn ($p) => $this->projectMatches($p, $like))
                    ->when(mb_stripos(self::MISC_LABEL, $q) !== false, fn ($w) => $w->orWhereNull('account_transactions.project_id'))
                    ->orWhereHas('invoice', fn ($i) => $i->where('original_file_name', 'like', $like));

                if ($payees) {
                    $w->orWhereIn('payee', $payees);
                }
                if ($amount !== null) {
                    $w->orWhereRaw('ABS(amount) = ?', [$amount])
                        ->orWhere('settled_amount', $amount)
                        ->orWhereHas('payments', fn ($p) => $p->where('amount', $amount));
                }
            })
            ->orderByDesc('id')
            ->limit(self::SEARCH_LIMIT + 1)
            ->get();

        $invoices = ! $canOther ? collect() : ProjectInvoiceDetail::query()
            ->where('invoice_type', 'material')
            ->whereNotNull('invoice_date')
            ->tap(fn ($q) => $this->onLiveProjectOrNone($q, 'project_invoice_details.project_id'))
            ->with(self::RELATIONS)
            ->where(function ($w) use ($like, $amount, $q) {
                $w->where('original_file_name', 'like', $like)
                    ->orWhereHas('project', fn ($p) => $this->projectMatches($p, $like))
                    ->when(mb_stripos(self::MISC_LABEL, $q) !== false, fn ($w) => $w->orWhereNull('project_invoice_details.project_id'));

                if ($amount !== null) {
                    $w->orWhere('amount', $amount)->orWhere('settled_amount', $amount);
                }
            })
            ->orderByDesc('id')
            ->limit(self::SEARCH_LIMIT + 1)
            ->get();

        $rows = $transactions
            ->flatMap(function ($t) use ($canExpected, $canOther) {
                if ((float) $t->amount <= 0) {
                    return [match ($this->sectionOf($t)) {
                        'com' => $this->commissionRow($t),
                        'lab' => $this->laborRow($t),
                        default => $this->otherRow($t),
                    }];
                }

                // A part-paid payment is both still expected and (partly) received.
                $items = $canExpected && $t->status === AccountTransaction::STATUS_OPEN ? [$this->expectedRow($t)] : [];

                return $canOther ? array_merge($items, $this->actualRowsOf($t)) : $items;
            })
            ->concat($invoices->map(fn ($i) => $this->materialRow($i)))
            ->sortByDesc(fn ($r) => $this->placedOn($r))
            ->values();
        $rows = $this->withReceivedHints($rows);

        return [
            'rows' => $rows->take(self::SEARCH_LIMIT)->all(),
            'truncated' => $rows->count() > self::SEARCH_LIMIT,
        ];
    }

    /** A project whose name, code, customer, partner, sub-contractor or finance option matches. */
    private function projectMatches($project, string $like): void
    {
        $project->where(function ($p) use ($like) {
            $p->where('project_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhereHas('customer', function ($c) use ($like) {
                    $c->where(function ($c) use ($like) {
                        $c->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereHas('salespartner', fn ($s) => $s->where('name', 'like', $like))
                            ->orWhereHas('subContractor', fn ($s) => $s->where('name', 'like', $like))
                            ->orWhereHas('finances.finance', fn ($f) => $f->where('name', 'like', $like));
                    });
                });
        });
    }

    /** The date a row is placed in a period by - the board's own key() - used to sort results. */
    private function placedOn(array $r): string
    {
        if (! in_array($r['b'], ['ear', 'aar'], true) && $r['status'] === AccountTransaction::STATUS_CLOSED && ! empty($r['paidOn'])) {
            return $r['paidOn'];
        }

        return (string) match ($r['b']) {
            'ear' => $r['expected'],
            'aar' => $r['received'],
            'com' => $r['due'] ?? $r['paymentReceived'],
            'lab' => $r['inspection'],
            default => $r['due'],
        };
    }

    /**
     * The sections `$user` may add to: one they can see, plus what adding it
     * takes elsewhere - Account Transactions Edit for a transaction (the
     * project page's Add button), Invoice Details for a material invoice (the
     * project's Invoices tab).
     */
    public function creatableSections(User $user): array
    {
        return array_values(array_filter($this->sections($user), fn ($b) => $user->can(
            $b === 'mat' ? self::PERMISSION_INVOICE : self::PERMISSION_CREATE
        )));
    }

    /**
     * Add a row to a section, as the project page would:
     *   ear  a payment expected (positive, Open)
     *   aar  a payment already received: added, then received in full or in
     *        part like any Expected AR payment (so it may raise the commission)
     *   com  a commission payable to the sales partner (negative, Open); due
     *        the Friday of the week after unless a due date is given
     *   lab  a labor payable to the sub-contractor (negative, Open), dated by
     *        the inspection approval date
     *   oth  a payable to any other payee (negative, Open)
     *   mat  a material invoice with its file, which also updates the
     *        project's actual material cost
     *
     * Any section takes `project_id` null: a miscellaneous payment (no
     * project, so no commission, no project cost to update, no sales partner /
     * sub-contractor on the row). `$data` is validated by the controller.
     *
     * @return array{item: string, commission: ?AccountTransaction}
     */
    public function create(string $section, array $data, ?UploadedFile $file = null): array
    {
        $project = $data['project_id'] === null ? null : Project::findOrFail($data['project_id']);
        $user = auth()->user();

        if ($section === 'mat') {
            $original = $file->getClientOriginalName();
            $path = $file->storeAs('invoices', time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $original), 'public');

            $invoice = DB::transaction(function () use ($project, $data, $original, $path, $user) {
                $invoice = ProjectInvoiceDetail::create([
                    'project_id' => $project?->id,
                    'invoice_type' => 'material',
                    'invoice_date' => Carbon::parse($data['invoice_date'])->toDateString(),
                    'amount' => round((float) $data['amount'], 2),
                    'notes' => $data['notes'] ?? null,
                    'file_name' => basename($path),
                    'original_file_name' => $original,
                    'file_path' => $path,
                    'uploaded_by' => $user?->id,
                ]);

                // On a project: its actual material cost follows, and the project's log has it.
                // With none (a miscellaneous invoice) the invoice's own log has it.
                if ($project) {
                    ProjectInvoiceDetail::syncProjectCost($project, 'material');
                }

                activity($project ? 'project' : 'ProjectInvoiceDetail')
                    ->performedOn($project ?? $invoice)
                    ->causedBy($user)
                    ->setEvent($project ? 'updated' : 'created')
                    ->withProperties(['project_invoice_detail_id' => $invoice->id])
                    ->log(($user?->name ?? 'System').($project
                        ? ' added an invoice detail from the Transaction Portal.'
                        : ' added a miscellaneous material invoice (no project) from the Transaction Portal.'));

                return $invoice;
            });

            return ['item' => 'i'.$invoice->id, 'commission' => null];
        }

        $in = in_array($section, ['ear', 'aar'], true);
        $amount = round(abs((float) $data['amount']), 2);
        $date = Carbon::parse($data['transaction_date'])->toDateString();
        $dueDate = match ($section) {
            'com' => Carbon::parse($data['due_date'] ?? CommissionPayableService::dueDate($date))->toDateString(),
            'oth' => empty($data['due_date']) ? null : Carbon::parse($data['due_date'])->toDateString(),
            default => null,
        };

        $items = [
            'project_id' => $project?->id,
            'payee' => match ($section) {
                'com' => 'sales_partner',
                'lab' => 'sub_contractor',
                default => $data['payee'],
            },
            'milestone' => $data['milestone'],
            'amount' => $in ? $amount : -$amount,
            'deduction_amount' => $in ? round((float) ($data['deduction_amount'] ?? 0), 2) : 0,
            'transaction_date' => $date,
            'due_date' => $dueDate,
            'transaction_details' => $data['transaction_details'],
            'status' => AccountTransaction::STATUS_OPEN,
        ];

        $transaction = DB::transaction(function () use ($items, $user) {
            $transaction = AccountTransaction::create($items);

            $activity = activity('AccountTransaction')
                ->performedOn($transaction)
                ->withProperties($items)
                ->setEvent('created');

            if ($user) {
                $activity->causedBy($user);
            }

            $activity->log(($user?->name ?? 'System').' created the account transaction from the Transaction Portal.');

            return $transaction;
        });

        if ($section !== 'aar') {
            return ['item' => 't'.$transaction->id, 'commission' => null];
        }

        $commission = $this->settleTransaction($transaction, (float) $data['received_amount'], $data['received_date']);

        return ['item' => 't'.$transaction->id, 'commission' => $commission];
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
            default => 'oth',
        };
    }

    public function permissionFor(string $section): string
    {
        return $section === 'ear' ? self::PERMISSION_EXPECTED : self::PERMISSION_OTHER;
    }

    /**
     * Record money received or paid on a transaction. Less than the balance is
     * a part payment: the row stays Open with what is left, unless `$final`
     * (the rest is not coming). A customer payment (positive) shows in Actual
     * AR for the day received, part payments too, and may raise the commission.
     *
     * @return AccountTransaction|null the commission payable this payment raised
     */
    public function settleTransaction(AccountTransaction $transaction, float $amount, string $date, bool $final = false): ?AccountTransaction
    {
        $date = Carbon::parse($date)->toDateString();

        $this->payments->record($transaction, $amount, $date, $final);

        if ((float) $transaction->amount > 0 && $transaction->project) {
            return $this->commissions->raiseForPayment($transaction->project, $date);
        }

        return null;
    }

    /** Undo one payment (from a row's payment history). The row reopens with its balance. */
    public function removePayment(TransactionPayment $payment): void
    {
        $this->payments->remove($payment);
    }

    /**
     * Delete a received payment (an Actual AR item), e.g. a duplicate. Soft
     * delete, logged, so it can be restored. A commission it helped raise is
     * NOT taken back: the page warns when the project has one.
     *
     * One of several payments on a row: only that payment goes, and the row is
     * expected again for what is left. A row's only payment (or a row closed
     * before payments were recorded one by one): the whole transaction goes.
     */
    public function deleteReceived(AccountTransaction|TransactionPayment $item): void
    {
        if ($item instanceof TransactionPayment) {
            $transaction = $item->transaction;

            if (! $transaction || $transaction->payments()->whereKeyNot($item->id)->exists()) {
                $this->payments->remove($item);

                return;
            }

            $item = $transaction;
        }

        $transaction = $item;

        DB::transaction(function () use ($transaction) {
            $transaction->payments()->get()->each->delete();
            $transaction->delete();

            $user = auth()->user();

            activity('AccountTransaction')
                ->performedOn($transaction)
                ->causedBy($user)
                ->withProperties($transaction->only(['project_id', 'payee', 'milestone', 'amount', 'transaction_date', 'settled_amount', 'settled_date', 'transaction_details']))
                ->setEvent('deleted')
                ->log(($user?->name ?? 'System').' deleted the received payment from the Transaction Portal.');
        });
    }

    /**
     * Hints on Actual AR rows, for spotting duplicates and what deleting one
     * leaves behind:
     *   imported         came from a spreadsheet import;
     *   twin             another payment on the project has the same amount;
     *   commissionAfter  the sales partner's commission was raised on or after
     *                    this payment was received (deleting it does not undo that).
     */
    private function withReceivedHints($rows)
    {
        $rows = collect($rows);
        $received = $rows->where('b', 'aar');

        if ($received->isEmpty()) {
            return $rows;
        }

        $projectIds = $received->pluck('projectId')->filter()->unique()->values()->all();

        $twins = AccountTransaction::whereIn('project_id', $projectIds)
            ->where('amount', '>', 0)
            ->selectRaw('project_id, ROUND(amount, 2) as rounded, COUNT(*) as copies')
            ->groupBy('project_id', DB::raw('ROUND(amount, 2)'))
            ->get()
            ->filter(fn ($g) => (int) $g->copies > 1)
            ->mapWithKeys(fn ($g) => [$g->project_id.'|'.number_format((float) $g->rounded, 2, '.', '') => true]);

        $lastCommission = AccountTransaction::whereIn('project_id', $projectIds)
            ->where('payee', 'sales_partner')
            ->where('amount', '<', 0)
            ->selectRaw('project_id, MAX(transaction_date) as last')
            ->groupBy('project_id')
            ->pluck('last', 'project_id');

        return $rows->map(function ($r) use ($twins, $lastCommission) {
            if ($r['b'] !== 'aar') {
                return $r;
            }

            // A miscellaneous payment has no project: no twin to compare with, no commission.
            $last = $r['projectId'] ? ($lastCommission[$r['projectId']] ?? null) : null;

            return $r + [
                'imported' => str_starts_with((string) $r['details'], 'Imported from'),
                'twin' => $r['projectId'] !== null && isset($twins[$r['projectId'].'|'.number_format((float) $r['expectedAmount'], 2, '.', '')]),
                'commissionAfter' => $last !== null && $r['received'] !== null && $this->date($last) >= $r['received'],
            ];
        });
    }

    /** Reopen a paid row (a mistake undone): every payment on it is removed. */
    public function reopenTransaction(AccountTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->payments->removeAll($transaction);
            $this->log($transaction, 'reopened', []);
        });
    }

    /** Pay a material invoice, in full or in part (see settleTransaction()). */
    public function settleInvoice(ProjectInvoiceDetail $invoice, float $amount, string $date, bool $final = false): void
    {
        $this->payments->record($invoice, $amount, $date, $final);
    }

    public function reopenInvoice(ProjectInvoiceDetail $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $this->payments->removeAll($invoice);
            $this->log($invoice, 'reopened', [], 'ProjectInvoiceDetail');
        });
    }

    /** Close an open row whose payments already cover it. */
    public function closePaid(AccountTransaction|ProjectInvoiceDetail $record): void
    {
        $this->payments->close($record);
    }

    /** Still to come on an open row: what the bulk bar receives / pays. */
    public function balanceOf(AccountTransaction|ProjectInvoiceDetail $record): float
    {
        return $this->payments->balance($record);
    }

    /** Payments received on customer payments, with their transaction loaded for the row. */
    private function receivedPayments(User $user): Builder
    {
        return TransactionPayment::query()
            ->whereNotNull('account_transaction_id')
            ->whereHas('transaction', fn ($t) => $this->onLiveProjectOrNone($t->filterByRole($user))
                ->where('amount', '>', 0))
            ->with(['transaction' => fn ($t) => $t->with(self::RELATIONS)]);
    }

    /** A customer payment's Actual AR items: one per payment, or its one settlement from before. */
    private function actualRowsOf(AccountTransaction $t): array
    {
        if ($t->payments->isNotEmpty()) {
            return $t->payments->map(fn ($p) => $this->actualRow($t, $p))->all();
        }

        return $t->status === AccountTransaction::STATUS_CLOSED && $t->settled_date ? [$this->actualRow($t)] : [];
    }

    /** Transactions on live projects, scoped the way Account Transactions already is. */
    private function transactions(User $user): Builder
    {
        return $this->onLiveProjectOrNone(AccountTransaction::query()->filterByRole($user))
            ->with(self::RELATIONS);
    }

    /**
     * A transaction (or invoice) on a live project, or on none: a
     * miscellaneous payment (a lender's bonus, a rebate, office costs...) has
     * no project. The roles narrowed to their own projects (Sales Person,
     * Sub-Contractor Manager) never see those, as filterByRole() reaches them
     * through the project.
     */
    private function onLiveProjectOrNone($query, string $column = 'account_transactions.project_id')
    {
        return $query->where(fn ($q) => $q->whereNull($column)
            ->orWhereHas('project', fn ($q) => $q->whereNull('projects.deleted_at')));
    }

    /**
     * Money owed out to `$payee`. An open row sits in the period of `$keyDate`
     * (and is carried forward while still open); a paid row sits in the period
     * it was PAID in. A row closed before the portal existed has no paid date,
     * so it stays on `$keyDate`.
     *
     * Open rows by `$mode`: ALL = due in the period or earlier, or batched for
     * a day of it; PAID_ONLY = none.
     */
    private function payables(User $user, ?string $payee, string $keyDate, Carbon $start, Carbon $end, string $mode = self::ALL): Builder
    {
        $period = [$start->toDateString(), $end->toDateString()];

        return $this->transactions($user)
            // null = every payee without a section of its own (no payee at all included).
            ->when($payee, fn ($q) => $q->where('payee', $payee), fn ($q) => $q->where(function ($q) {
                $q->whereNotIn('payee', self::OWN_SECTION_PAYEES)->orWhereNull('payee');
            }))
            ->where('amount', '<', 0)
            ->where(function ($q) use ($keyDate, $end, $period, $mode) {
                $q->where(function ($q) use ($keyDate, $period) {
                    $q->where('status', AccountTransaction::STATUS_CLOSED)
                        ->whereRaw("COALESCE(settled_date, $keyDate) between ? and ?", $period);
                });

                if ($mode === self::ALL) {
                    $q->orWhere(function ($q) use ($keyDate, $end, $period) {
                        $q->where('status', AccountTransaction::STATUS_OPEN)
                            ->where(fn ($q) => $q->whereRaw("$keyDate <= ?", [$end->toDateString()])
                                ->orWhereBetween('pay_run_date', $period));
                    });
                }
            });
    }

    /**
     * Material invoices. Open: due (invoice date + N days) in the period, or
     * earlier. Paid: paid in the period (an invoice paid before the portal
     * existed has no paid date and stays on its due date). Open ones follow
     * `$mode` the way payables() does (batched for a day of the period counts).
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
            ->tap(fn ($q) => $this->onLiveProjectOrNone($q, 'project_invoice_details.project_id'))
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
                    $q->orWhere(function ($q) use ($invoicedFor, $period) {
                        $q->where('status', ProjectInvoiceDetail::STATUS_OPEN)
                            ->where(fn ($q) => $q->whereDate('invoice_date', '<=', $invoicedFor[1])
                                ->orWhereBetween('pay_run_date', $period));
                    });
                }
            })
            ->get();
    }

    /** Still expected: its amount is what is left to come once part payments are taken off. */
    private function expectedRow(AccountTransaction $t): array
    {
        return $this->projectFields($t->project) + [
            'id' => 't'.$t->id,
            'b' => 'ear',
            'milestone' => $t->milestone,
            'expected' => $this->date($t->transaction_date),
            'amount' => $this->payments->balance($t),
            'status' => $t->status,
        ] + $this->paymentFields($t);
    }

    /**
     * One payment received (id p<payment>), or - for a row closed before
     * payments were recorded one by one - its one settlement (id t<transaction>).
     * Always 'Closed': the money is in. `partOpen` = its row still expects more.
     */
    private function actualRow(AccountTransaction $t, ?TransactionPayment $p = null): array
    {
        return $this->projectFields($t->project) + [
            'id' => $p ? 'p'.$p->id : 't'.$t->id,
            'b' => 'aar',
            'transactionId' => $t->id,
            'milestone' => $t->milestone,
            'received' => $this->date($p ? $p->paid_on : $t->settled_date),
            'amount' => round((float) ($p ? $p->amount : $t->settled_amount), 2),
            'expectedAmount' => round((float) $t->amount, 2),
            'details' => $t->transaction_details,
            'status' => AccountTransaction::STATUS_CLOSED,
            'partOpen' => $t->status === AccountTransaction::STATUS_OPEN,
        ] + $this->paymentFields($t);
    }

    /** A payable shows what is left to pay while open, and its full amount once paid. */
    private function payableAmount(AccountTransaction|ProjectInvoiceDetail $record): float
    {
        return $record->status === AccountTransaction::STATUS_OPEN
            ? $this->payments->balance($record)
            : $this->payments->fullAmount($record);
    }

    /** What the drawer shows of a row's payments: total, paid so far, balance, and each payment. */
    private function paymentFields(AccountTransaction|ProjectInvoiceDetail $record): array
    {
        return [
            'total' => $this->payments->fullAmount($record),
            'paid' => $this->payments->paid($record),
            'balance' => $this->payments->balance($record),
            'payments' => $this->payments->history($record),
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
            'amount' => $this->payableAmount($t),
            'status' => $t->status,
            'paidOn' => $this->date($t->settled_date),
            'payRun' => $this->date($t->pay_run_date),
        ] + $this->paymentFields($t);
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
            'amount' => $this->payableAmount($t),
            'status' => $t->status,
            'paidOn' => $this->date($t->settled_date),
            'payRun' => $this->date($t->pay_run_date),
            'invoiceNo' => $invoice ? app(SubContractorPayableService::class)->invoiceNumber($invoice) : null,
            'file' => $invoice?->original_file_name,
        ] + $this->paymentFields($t);
    }

    /** A payable to anyone without a section of their own. Due on its own date. */
    private function otherRow(AccountTransaction $t): array
    {
        return $this->projectFields($t->project) + [
            'id' => 't'.$t->id,
            'b' => 'oth',
            'payee' => $t->payee ? $t->payee_label : null,
            'milestone' => $t->milestone,
            'details' => $t->transaction_details,
            'date' => $this->date($t->transaction_date),
            'due' => $this->date($t->due_date ?? $t->transaction_date),
            'amount' => $this->payableAmount($t),
            'status' => $t->status,
            'paidOn' => $this->date($t->settled_date),
            'payRun' => $this->date($t->pay_run_date),
        ] + $this->paymentFields($t);
    }

    private function materialRow(ProjectInvoiceDetail $i): array
    {
        return $this->projectFields($i->project) + [
            'id' => 'i'.$i->id,
            'b' => 'mat',
            'invoiceDate' => $this->date($i->invoice_date),
            'due' => $this->date(Carbon::parse($i->invoice_date)->addDays($this->materialDueDays())),
            'amount' => $this->payableAmount($i),
            'status' => $i->status,
            'paidOn' => $this->date($i->settled_date),
            'payRun' => $this->date($i->pay_run_date),
            'file' => $i->original_file_name,
        ] + $this->paymentFields($i);
    }

    /** A row with no project is a miscellaneous payment (rows on deleted projects are never loaded). */
    private function projectFields(?Project $project): array
    {
        $customer = $project?->customer;

        return [
            'projectId' => $project?->id,
            'project' => $project?->project_name ?? self::MISC_LABEL,
            'misc' => $project === null,
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
