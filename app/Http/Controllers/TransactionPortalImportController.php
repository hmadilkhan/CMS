<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Services\TransactionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Transaction Portal -> Import: upload a spreadsheet, check the preview, then
 * import. The parsed rows wait in the cache between the two steps, keyed to
 * the user, so the import writes exactly what the preview showed.
 */
class TransactionPortalImportController extends Controller
{
    private const CACHE_MINUTES = 120;

    public function __construct(private TransactionImportService $importer) {}

    public function create()
    {
        return view('transaction-portal.import', [
            'step' => 'upload',
            'today' => now()->toDateString(),
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:'.AccountTransaction::STATUS_OPEN.','.AccountTransaction::STATUS_CLOSED],
        ], [
            'file.mimes' => 'Upload an Excel (.xlsx, .xls) or CSV file.',
        ]);

        $file = $validated['file'];

        try {
            $raw = $this->importer->read($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['file' => 'The file could not be read. Save it as .xlsx or .csv and try again.']);
        }

        if (empty($raw)) {
            return back()->withInput()->withErrors(['file' => 'The file has no rows to import.']);
        }

        if (count($raw) > TransactionImportService::MAX_ROWS) {
            return back()->withInput()->withErrors(['file' => 'The file has more than '.TransactionImportService::MAX_ROWS.' rows. Split it and import each part.']);
        }

        $user = $request->user();
        $rows = $this->importer->resolve($raw, $user, $validated['date'], $validated['status']);
        $token = (string) Str::uuid();

        Cache::put($this->cacheKey($user->id, $token), [
            'file' => $file->getClientOriginalName(),
            'rows' => $rows,
        ], now()->addMinutes(self::CACHE_MINUTES));

        $needsChoice = collect($rows)->contains(fn ($r) => ! $r['project_id'] || $r['match'] === 'partial');

        return view('transaction-portal.import', [
            'step' => 'preview',
            'token' => $token,
            'fileName' => $file->getClientOriginalName(),
            'rows' => $rows,
            'projects' => $needsChoice ? $this->importer->pickableProjects($user) : [],
            'payees' => array_values(AccountTransaction::payeeChoices()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'rows' => ['array'],
            'rows.*' => ['array'],
        ]);

        $user = $request->user();
        $key = $this->cacheKey($user->id, $validated['token']);
        $upload = Cache::get($key);

        if (! $upload) {
            return redirect()->route('transaction-portal.import')
                ->withErrors(['file' => 'That upload has expired. Upload the file again.']);
        }

        $result = $this->importer->import($upload['rows'], $validated['rows'] ?? [], $user, $upload['file']);

        Cache::forget($key);

        return redirect()->route('transaction-portal.import')->with('importResult', $result + ['file' => $upload['file']]);
    }

    /** A blank file with the columns the import reads. */
    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Project Name', 'Payee', 'Milestone', 'Amount', 'Date', 'Status', 'Details']);
            fputcsv($out, ['Paul Wilhite - Storage', 'Maxwell Power', 'Milestone 1', '9072.00', now()->format('m/d/Y'), 'Open', '']);
            fclose($out);
        }, 'account-transactions-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    private function cacheKey(int $userId, string $token): string
    {
        return "transaction-import:{$userId}:{$token}";
    }
}
