<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\FinanceOption;
use App\Models\ProjectInvoiceDetail;
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
                'payRunUrl' => route('transaction-portal.pay-run'),
                'searchUrl' => route('transaction-portal.search'),
                'searchLimit' => TransactionPortalService::SEARCH_LIMIT,
                'exportUrl' => route('transaction-portal.export'),
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
     * Close one or more open rows. One row may carry the amount actually
     * received / paid; several (the bulk bar) are each closed at their own
     * amount.
     */
    public function settle(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*' => 'required|string|max:20',
            'date' => 'required|date',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        $single = count($validated['items']) === 1 && isset($validated['amount']);
        $closed = 0;
        $commissions = [];

        foreach ($validated['items'] as $item) {
            [$record, $section] = $this->resolve($request, $item);

            if ($record->status !== AccountTransaction::STATUS_OPEN) {
                continue;
            }

            if ($record instanceof ProjectInvoiceDetail) {
                $this->portal->settleInvoice($record, $single ? (float) $validated['amount'] : (float) $record->amount, $validated['date']);
            } else {
                $full = $section === 'ear'
                    ? (float) $record->amount - (float) $record->deduction_amount
                    : abs((float) $record->amount);

                $commission = $this->portal->settleTransaction($record, $single ? (float) $validated['amount'] : $full, $validated['date']);

                if ($commission) {
                    $commissions[] = [
                        'amount' => abs((float) $commission->amount),
                        'due' => $commission->due_date ? Carbon::parse($commission->due_date)->toDateString() : null,
                    ];
                }
            }

            $closed++;
        }

        return response()->json(['closed' => $closed, 'commissions' => $commissions]);
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
            fputcsv($out, ['Section', 'Project Name', 'Finance Option', 'Sales Partner', 'Sub-Contractor', 'Date', 'Due Date', 'Amount', 'Status', 'Received / Paid On', 'Pay Run', 'Payee']);

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
     * The record behind a board id ("t12" = transaction 12, "i5" = invoice 5),
     * refused unless the user holds the permission of the section it is in.
     *
     * @return array{0: AccountTransaction|ProjectInvoiceDetail, 1: string}
     */
    private function resolve(Request $request, string $item): array
    {
        $user = $request->user();

        if (preg_match('/^t(\d+)$/', $item, $match)) {
            $record = AccountTransaction::filterByRole($user)->findOrFail((int) $match[1]);
            $section = $this->portal->sectionOf($record);
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
