<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\FinanceOption;
use App\Models\ProjectInvoiceDetail;
use App\Models\TransactionPayment;
use App\Services\TransactionPortalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Finance -> Transaction Portal: money in and out, week by week.
 *
 * The page draws itself from `data` (JSON for one period) and closes rows
 * through `settle` / `reopen`. Every row a request names is checked against
 * the permission of its own section, not just the page's.
 */
class TransactionPortalController extends Controller
{
    /** A period longer than this is not a week or a month. */
    private const MAX_RANGE_DAYS = 62;

    public function __construct(private TransactionPortalService $portal) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $sections = $this->portal->sections($user);

        // The page has no sidebar, so it carries its own way back: the
        // dashboard, or Zones for a Funding Manager who has no dashboard.
        [$backUrl, $backLabel] = $user->can('Dashboard') || ! $user->isZoneOnlyUser()
            ? [route('dashboard'), 'Dashboard']
            : [route('projects.index', ['tab' => 'zones']), 'Zones'];

        return view('transaction-portal.index', [
            'sections' => $sections,
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
            'financeOptions' => FinanceOption::orderBy('name')->pluck('name')->unique()->values(),
            // One variable: Blade's @json splits its argument on commas.
            'portalConfig' => [
                'sections' => $sections,
                'today' => now()->toDateString(),
                'dataUrl' => route('transaction-portal.data'),
                'settleUrl' => route('transaction-portal.settle'),
                'reopenUrl' => route('transaction-portal.reopen'),
                'removePaymentUrl' => route('transaction-portal.remove-payment'),
                'payRunUrl' => route('transaction-portal.pay-run'),
                'searchUrl' => route('transaction-portal.search'),
                'searchLimit' => TransactionPortalService::SEARCH_LIMIT,
                'exportUrl' => route('transaction-portal.export'),
                'deleteUrl' => route('transaction-portal.delete-received'),
                // Received payments (Actual AR) can be ticked and deleted only with both permissions.
                'canDelete' => in_array('aar', $sections, true) && $user->can(TransactionPortalService::PERMISSION_DELETE),
            ],
        ]);
    }

    public function data(Request $request)
    {
        [$start, $end] = $this->range($request);

        return response()->json([
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'rows' => $this->portal->rows($request->user(), $start, $end),
        ]);
    }

    /** The "All time" search: matching rows of every period, newest first. */
    public function search(Request $request)
    {
        $validated = $request->validate(['q' => 'required|string|min:2|max:100']);

        return response()->json($this->portal->search($request->user(), $validated['q']));
    }

    /**
     * Record money received / paid on open rows. One row may carry the amount
     * actually received / paid: less than its balance is a part payment and the
     * row stays Open with what is left, unless `final` (the rest is not
     * coming). Several (the bulk bar) are each received / paid in full - their
     * balance.
     */
    public function settle(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*' => 'required|string|max:20',
            'date' => 'required|date',
            'amount' => 'nullable|numeric|min:0.01',
            'final' => 'sometimes|boolean',
        ]);

        $single = count($validated['items']) === 1 && isset($validated['amount']);
        $closed = 0;
        $partial = [];
        $commissions = [];

        foreach ($validated['items'] as $item) {
            [$record, $section] = $this->resolve($request, $item);

            if ($record instanceof TransactionPayment || $record->status !== AccountTransaction::STATUS_OPEN) {
                continue;
            }

            $balance = $this->portal->balanceOf($record);
            $amount = $single ? (float) $validated['amount'] : $balance;
            $final = $single ? $request->boolean('final') : true;

            if ($amount <= 0) {
                // Nothing left to receive / pay: it only needed closing.
                $this->portal->closePaid($record);
                $closed++;

                continue;
            }

            if ($record instanceof ProjectInvoiceDetail) {
                $this->portal->settleInvoice($record, $amount, $validated['date'], $final);
            } else {
                $commission = $this->portal->settleTransaction($record, $amount, $validated['date'], $final);

                if ($commission) {
                    $commissions[] = [
                        'amount' => abs((float) $commission->amount),
                        'due' => $commission->due_date ? Carbon::parse($commission->due_date)->toDateString() : null,
                    ];
                }
            }

            $record->refresh();

            if ($record->status === AccountTransaction::STATUS_CLOSED) {
                $closed++;
            } else {
                $partial[] = [
                    'item' => $item,
                    'paid' => round((float) $record->settled_amount, 2),
                    'balance' => $this->portal->balanceOf($record),
                ];
            }
        }

        return response()->json(['closed' => $closed, 'partial' => $partial, 'commissions' => $commissions]);
    }

    /**
     * Undo one payment from a row's payment history (recorded by mistake). The
     * row is expected / payable again for what is left. Needs the permission
     * of the section the row is in.
     */
    public function removePayment(Request $request)
    {
        $validated = $request->validate(['payment' => 'required|integer']);
        $payment = TransactionPayment::findOrFail($validated['payment']);
        $user = $request->user();

        if ($payment->account_transaction_id) {
            $record = AccountTransaction::filterByRole($user)->findOrFail($payment->account_transaction_id);
            $section = $this->portal->sectionOf($record);
        } else {
            ProjectInvoiceDetail::where('invoice_type', 'material')->findOrFail($payment->project_invoice_detail_id);
            $section = 'mat';
        }

        abort_unless($section && $user->can($this->portal->permissionFor($section)), 403);

        $this->portal->removePayment($payment);

        return response()->json(['removed' => true]);
    }

    /**
     * Put open payables in the pay run for `date` (picked on Thursday for
     * Friday), or take them out (`date` empty). Once a week has a pay run, its
     * other open payables move to the next week. Expected AR has no pay run.
     */
    public function payRun(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*' => 'required|string|max:20',
            'date' => 'nullable|date|after_or_equal:today',
        ], [
            'date.after_or_equal' => 'A pay run cannot be planned for a day that has passed.',
        ]);

        $updated = 0;

        foreach ($validated['items'] as $item) {
            [$record, $section] = $this->resolve($request, $item);

            abort_unless(in_array($section, TransactionPortalService::PAYABLE_SECTIONS, true), 422, 'Only payables (money out) go in a pay run.');

            if ($this->portal->setPayRun($record, $validated['date'] ?? null)) {
                $updated++;
            }
        }

        return response()->json(['updated' => $updated]);
    }

    /**
     * Delete received payments (Actual AR rows), e.g. duplicates. Needs the
     * Actual AR section's permission (checked per row) AND Account Transactions
     * Edit; only a closed positive row qualifies. Soft delete, logged.
     */
    public function deleteReceived(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*' => 'required|string|max:20',
        ]);

        abort_unless($request->user()->can(TransactionPortalService::PERMISSION_DELETE), 403);

        // Check every row before deleting any, so a bad one stops the whole batch.
        $records = collect($validated['items'])->unique()->map(function ($item) use ($request) {
            [$record, $section] = $this->resolve($request, $item);

            abort_unless($section === 'aar', 422, 'Only received payments (Actual AR) can be deleted here.');

            return $record;
        });

        $records->each(fn ($record) => $this->portal->deleteReceived($record));

        return response()->json(['deleted' => $records->count()]);
    }

    /** Put a paid payable back to Open (a mistake undone). */
    public function reopen(Request $request)
    {
        $validated = $request->validate(['item' => 'required|string|max:20']);

        [$record, $section] = $this->resolve($request, $validated['item']);

        abort_unless(in_array($section, TransactionPortalService::PAYABLE_SECTIONS, true), 422, 'Only a paid payable can be reopened.');

        if ($record instanceof ProjectInvoiceDetail) {
            $this->portal->reopenInvoice($record);
        } else {
            $this->portal->reopenTransaction($record);
        }

        return response()->json(['reopened' => true]);
    }

    /** The period's rows as a CSV (opens in Excel), the sections the user may see only. */
    public function export(Request $request)
    {
        [$start, $end] = $this->range($request);
        $rows = $this->portal->rows($request->user(), $start, $end);

        $titles = [
            'ear' => 'Expected Accounts Receivable',
            'aar' => 'Actual Accounts Receivable',
            'com' => 'Commission Payable',
            'lab' => 'Labor Payable',
            'mat' => 'Material Invoices Payable',
            'oth' => 'Other Payables',
        ];
        $dateOf = fn ($r) => $r['expected'] ?? $r['received'] ?? $r['paymentReceived'] ?? $r['inspection'] ?? $r['invoiceDate'] ?? $r['date'] ?? '';

        // A cell starting with = + - @ (or a tab / CR) would run as a formula in Excel.
        $safe = fn ($value) => is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;

        $filename = 'transaction-portal-'.$start->toDateString().'-to-'.$end->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows, $titles, $dateOf, $safe) {
            $out = fopen('php://output', 'w');
            // Payee last, so the columns people already use keep their place.
            // Full Amount / Paid So Far last too: an open row's Amount is its balance once part paid.
            fputcsv($out, ['Section', 'Project Name', 'Finance Option', 'Sales Partner', 'Sub-Contractor', 'Date', 'Due Date', 'Amount', 'Status', 'Received / Paid On', 'Pay Run', 'Payee', 'Full Amount', 'Paid So Far']);

            foreach ($rows as $r) {
                fputcsv($out, array_map($safe, [
                    $titles[$r['b']],
                    $r['project'],
                    $r['finance'],
                    $r['partner'],
                    $r['sub'],
                    $dateOf($r),
                    $r['due'] ?? '',
                    number_format((float) $r['amount'], 2, '.', ''),
                    $r['status'],
                    $r['received'] ?? $r['paidOn'] ?? '',
                    $r['payRun'] ?? '',
                    $r['payee'] ?? '',
                    isset($r['total']) ? number_format((float) $r['total'], 2, '.', '') : '',
                    isset($r['paid']) ? number_format((float) $r['paid'], 2, '.', '') : '',
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: Carbon, 1: Carbon} the requested period, this week by default */
    private function range(Request $request): array
    {
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);

        $start = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : now()->startOfWeek((int) config('transaction_portal.week_starts_on', Carbon::SUNDAY))->startOfDay();
        $end = $request->filled('end') ? Carbon::parse($request->input('end'))->startOfDay() : $start->copy()->addDays(6);

        abort_if($start->diffInDays($end) > self::MAX_RANGE_DAYS, 422, 'Choose a week or a month.');

        return [$start, $end];
    }

    /**
     * The record behind a board id ("t12" = transaction 12, "i5" = invoice 5,
     * "p7" = payment 7 received on a customer payment, an Actual AR item),
     * refused unless the user holds the permission of the section it is in.
     *
     * @return array{0: AccountTransaction|ProjectInvoiceDetail|TransactionPayment, 1: string}
     */
    private function resolve(Request $request, string $item): array
    {
        $user = $request->user();

        if (preg_match('/^t(\d+)$/', $item, $match)) {
            $record = AccountTransaction::filterByRole($user)->findOrFail((int) $match[1]);
            $section = $this->portal->sectionOf($record);
        } elseif (preg_match('/^p(\d+)$/', $item, $match)) {
            $record = TransactionPayment::whereNotNull('account_transaction_id')->findOrFail((int) $match[1]);
            $transaction = AccountTransaction::filterByRole($user)->findOrFail($record->account_transaction_id);
            $section = (float) $transaction->amount > 0 ? 'aar' : null;
        } elseif (preg_match('/^i(\d+)$/', $item, $match)) {
            $record = ProjectInvoiceDetail::where('invoice_type', 'material')->findOrFail((int) $match[1]);
            $section = 'mat';
        } else {
            abort(404);
        }

        abort_unless($section, 404);
        abort_unless($user->can($this->portal->permissionFor($section)), 403);

        return [$record, $section];
    }
}
