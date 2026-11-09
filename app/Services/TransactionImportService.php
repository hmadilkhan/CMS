<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Payee;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Transaction Portal -> Import: account transactions from a spreadsheet.
 *
 * Two steps, and nothing is written until the second:
 *
 *   read() + resolve()  the file's rows, each matched to a project and payee
 *                       and checked for duplicates - shown as an editable
 *                       preview, where a missing value can be filled in and an
 *                       unmatched row given its project (closest names first);
 *   import()            the rows the user kept, with the values they left.
 *
 * Only what is in the file is written: no commission, finance milestone or
 * Labor Payable is raised by an import (unlike closing a payment in the portal).
 *
 * The file may have a header row (columns are then found by name, in any
 * order) or none, in which case the columns are read in this order:
 * Project Name, Payee, Milestone, Amount, Date, Status, Details.
 */
class TransactionImportService
{
    public const MAX_ROWS = 1000;

    private const POSITIONAL = ['project', 'payee', 'milestone', 'amount', 'date', 'status', 'details'];

    /** Header text (normalised) => field. */
    private const HEADERS = [
        'project' => 'project', 'projectname' => 'project', 'name' => 'project', 'customer' => 'project', 'customername' => 'project',
        'code' => 'code', 'projectcode' => 'code',
        'payee' => 'payee', 'paidto' => 'payee', 'paidby' => 'payee', 'from' => 'payee',
        'milestone' => 'milestone',
        'amount' => 'amount',
        'deduction' => 'deduction', 'deductionamount' => 'deduction',
        'date' => 'date', 'transactiondate' => 'date', 'expecteddate' => 'date',
        'status' => 'status',
        'details' => 'details', 'transactiondetails' => 'details', 'notes' => 'details', 'description' => 'details',
    ];

    /** A trailing " - Solar" / " - Storage" names the half of a split milestone, not the project. */
    private const PART_SUFFIX = '/\s*-\s*(solar|storage|battery)\s*$/i';

    private ?array $index = null;

    /** canAccessProject() answers, per user and project, for one request. */
    private array $visibility = [];

    public function __construct(private ProjectService $projects) {}

    /**
     * The file's data rows, raw: [line, project, code, payee, milestone, amount,
     * deduction, date, status, details] - blank rows dropped.
     */
    public function read(string $path, string $extension): array
    {
        $reader = IOFactory::createReader(match (strtolower($extension)) {
            'csv', 'txt' => 'Csv',
            'xls' => 'Xls',
            default => 'Xlsx',
        });
        $reader->setReadDataOnly(true);

        $cells = $reader->load($path)->getSheet(0)->toArray(null, true, false, false);
        $columns = null;
        $rows = [];

        foreach ($cells as $i => $cellRow) {
            $values = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $cellRow);

            if (collect($values)->every(fn ($v) => $v === null || $v === '')) {
                continue;
            }

            if ($columns === null) {
                $columns = $this->headerColumns($values);

                if ($columns !== null) {
                    continue; // that row was the header
                }

                $columns = array_flip(self::POSITIONAL);
            }

            $row = ['line' => $i + 1];

            foreach (['project', 'code', 'payee', 'milestone', 'amount', 'deduction', 'date', 'status', 'details'] as $field) {
                $row[$field] = isset($columns[$field]) ? ($values[$columns[$field]] ?? null) : null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Each raw row matched and checked, ready to preview. `$defaults` gives the
     * date and status of rows whose file has none.
     */
    public function resolve(array $raw, User $user, string $defaultDate, string $defaultStatus): array
    {
        $payees = Payee::all()->keyBy(fn ($p) => Str::lower($p->label));
        $seen = [];
        $rows = [];

        foreach ($raw as $r) {
            $errors = [];
            $name = trim((string) $r['project']);

            [$match, $projectId, $candidates, $part] = $this->matchProject($name, trim((string) $r['code']), $user);

            // Nothing matched: offer the closest names, best first.
            if ($match === 'none' && $name !== '') {
                $candidates = $this->similar($name, $user);
            }

            $milestone = trim((string) $r['milestone']);
            if ($milestone === '') {
                $errors[] = 'Milestone is missing.';
            } elseif ($part && stripos($milestone, $part) === false) {
                // "Jun Du - Storage" + "Milestone 1" -> "Storage Milestone 1" (the client's wording).
                $milestone = $part.' '.$milestone;
            }

            $payeeLabel = Payee::normaliseLabel((string) $r['payee']);
            $payee = $payeeLabel === '' ? null : ($payees[Str::lower($payeeLabel)] ?? Payee::where('key', Str::slug($payeeLabel, '_'))->first());
            if ($payeeLabel === '') {
                $errors[] = 'Payee is missing.';
            }

            $amount = $this->amount($r['amount']);
            if ($amount === null || round($amount, 2) == 0.0) {
                $errors[] = 'Amount is missing or not a number.';
            }

            $deduction = $this->amount($r['deduction']) ?? 0.0;

            $date = $this->date($r['date']);
            if ($date === false) {
                $errors[] = 'Date "'.$r['date'].'" is not a date.';
            }

            $status = $this->status($r['status']);
            if ($status === false) {
                $errors[] = 'Status "'.$r['status'].'" should be Open or Closed.';
            }

            if ($name === '' && trim((string) $r['code']) === '') {
                $errors[] = 'Project name is missing.';
            }

            $row = [
                'line' => $r['line'],
                'name' => $name,
                'code' => trim((string) $r['code']) ?: null,
                'match' => $match,
                'project_id' => $projectId,
                'project_name' => $projectId ? $this->projectIndex()['projects'][$projectId] : null,
                'candidates' => $candidates,
                'payee_label' => $payee?->label ?? $payeeLabel,
                'payee_key' => $payee?->key,
                'payee_new' => $payee === null && $payeeLabel !== '',
                'milestone' => $milestone,
                'amount' => $amount === null ? null : round($amount, 2),
                'deduction' => round($deduction, 2),
                // An unreadable date is left empty (not the default), so the
                // preview will not import the row until a date is filled in.
                'date' => $date === false ? '' : ($date ?: $defaultDate),
                'status' => $status ?: $defaultStatus,
                'details' => trim((string) $r['details']) ?: null,
                'errors' => $errors,
                'duplicate' => null,
            ];

            if ($projectId && empty($errors)) {
                $key = $this->duplicateKey($projectId, $row['payee_key'] ?? 'new:'.Str::lower($payeeLabel), $milestone, $row['amount']);

                if (isset($seen[$key])) {
                    $row['duplicate'] = 'Same as row '.$seen[$key].' of this file.';
                } elseif ($existing = $this->existing($projectId, $row['payee_key'], $milestone, $row['amount'])) {
                    $row['duplicate'] = 'Already in Account Transactions ('.Carbon::parse($existing->transaction_date)->format('m/d/Y').', '.$existing->status.').';
                }

                $seen[$key] ??= $r['line'];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Write the rows the user kept, with the values as they left them in the
     * preview. `$input` is keyed by the file's line number: include, project,
     * payee, milestone, amount, date, status, details. Only lines of this
     * upload count; a kept row that is still incomplete is skipped and listed.
     *
     * @return array{created: int, skipped: array<int, array{line: int, name: string, reason: string}>}
     */
    public function import(array $rows, array $input, User $user, string $fileName): array
    {
        $created = 0;
        $skipped = [];

        DB::transaction(function () use ($rows, $input, $user, $fileName, &$created, &$skipped) {
            foreach ($rows as $row) {
                $edit = $input[$row['line']] ?? null;

                if (! $edit || empty($edit['include'])) {
                    continue; // left out on purpose
                }

                [$clean, $errors] = $this->check($edit, $user);

                if ($errors) {
                    $skipped[] = ['line' => $row['line'], 'name' => $row['name'], 'reason' => implode(' ', $errors)];

                    continue;
                }

                $closed = $clean['status'] === AccountTransaction::STATUS_CLOSED;

                $items = [
                    'project_id' => $clean['project_id'],
                    'payee' => Payee::findOrCreateByLabel($clean['payee'])->key,
                    'milestone' => $clean['milestone'],
                    'amount' => $clean['amount'],
                    'deduction_amount' => $row['deduction'],
                    'transaction_date' => $clean['date'],
                    'transaction_details' => $clean['details'] ?? 'Imported from '.$fileName,
                    'status' => $clean['status'],
                    'settled_amount' => $closed ? round(abs($clean['amount'] - $row['deduction']), 2) : null,
                    'settled_date' => $closed ? $clean['date'] : null,
                ];

                $transaction = AccountTransaction::create($items);

                activity('AccountTransaction')
                    ->performedOn($transaction)
                    ->causedBy($user)
                    ->withProperties($items + ['import_file' => $fileName, 'import_line' => $row['line']])
                    ->setEvent('created')
                    ->log($user->name.' imported the account transaction from '.$fileName.' (row '.$row['line'].').');

                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** One edited row, cleaned: [values, errors]. */
    private function check(array $edit, User $user): array
    {
        $errors = [];
        $projectId = (int) ($edit['project'] ?? 0);

        if (! $projectId) {
            $errors[] = 'No project chosen.';
        } elseif (! isset($this->projectIndex()['projects'][$projectId]) || ! $this->visible($user, $projectId)) {
            $errors[] = 'You cannot access the project chosen.';
        }

        $payee = Payee::normaliseLabel((string) ($edit['payee'] ?? ''));
        if ($payee === '' || ! preg_match('/[\pL\pN]/u', $payee) || mb_strlen($payee) > 100) {
            $errors[] = 'Payee is missing.';
        }

        $milestone = trim((string) ($edit['milestone'] ?? ''));
        if ($milestone === '' || mb_strlen($milestone) > 255) {
            $errors[] = 'Milestone is missing.';
        }

        $amount = $this->amount($edit['amount'] ?? null);
        if ($amount === null || round($amount, 2) == 0.0) {
            $errors[] = 'Amount is missing or not a number.';
        }

        $date = $this->date($edit['date'] ?? null);
        if (! $date) {
            $errors[] = 'Date is missing.';
        }

        $status = $this->status($edit['status'] ?? null);
        if (! $status) {
            $errors[] = 'Status should be Open or Closed.';
        }

        return [[
            'project_id' => $projectId,
            'payee' => $payee,
            'milestone' => $milestone,
            'amount' => $amount === null ? null : round($amount, 2),
            'date' => $date,
            'status' => $status,
            'details' => trim((string) ($edit['details'] ?? '')) ?: null,
        ], $errors];
    }

    /**
     * Projects whose name is close to `$name`, best first, as id => name: a
     * typo ("Jinagtao-Liu") or a different spelling still finds its project.
     */
    private function similar(string $name, User $user, int $limit = 6): array
    {
        $key = $this->norm(preg_replace(self::PART_SUFFIX, '', $name));
        $tokens = collect(preg_split('/[^a-z0-9]+/', Str::lower(preg_replace(self::PART_SUFFIX, '', $name))))
            ->filter(fn ($t) => strlen($t) >= 3)->values();

        if ($key === '') {
            return [];
        }

        $scores = [];

        foreach ($this->projectIndex()['projects'] as $id => $projectName) {
            $candidate = $this->norm($projectName);
            similar_text($key, $candidate, $percent);

            // A whole word of the name in the project's name counts as much as close spelling.
            $words = $tokens->filter(fn ($t) => str_contains($candidate, $t))->count();
            $score = $percent + ($tokens->isEmpty() ? 0 : 40 * $words / $tokens->count());

            if ($score >= 55) {
                $scores[$id] = $score;
            }
        }

        arsort($scores);

        return collect(array_slice($scores, 0, $limit * 3, true))
            ->keys()
            ->filter(fn ($id) => $this->visible($user, $id))
            ->take($limit)
            ->mapWithKeys(fn ($id) => [$id => $this->projectIndex()['projects'][$id]])
            ->all();
    }

    /** Projects a user may pick for a row the file could not place, as id => name. */
    public function pickableProjects(User $user): array
    {
        return collect($this->projectIndex()['projects'])
            ->filter(fn ($name, $id) => $this->visible($user, $id))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }

    /**
     * [match, project id, candidates (id => name), part]. Match is one of
     * code / exact / customer / partial / ambiguous / none.
     */
    private function matchProject(string $name, string $code, User $user): array
    {
        $index = $this->projectIndex();

        if ($code !== '' && isset($index['codes'][Str::lower($code)])) {
            $id = $index['codes'][Str::lower($code)];

            return $this->visible($user, $id) ? ['code', $id, [], null] : ['none', null, [], null];
        }

        // The whole name first: a real project may itself end in " - <something>".
        $attempts = [[$name, null]];
        if (preg_match(self::PART_SUFFIX, $name, $m)) {
            $attempts[] = [preg_replace(self::PART_SUFFIX, '', $name), Str::title($m[1])];
        }

        foreach ($attempts as [$candidate, $part]) {
            $key = $this->norm($candidate);

            if ($key === '') {
                continue;
            }

            foreach (['exact' => $index['names'][$key] ?? [], 'customer' => $index['customers'][$key] ?? []] as $match => $ids) {
                $ids = array_values(array_filter($ids, fn ($id) => $this->visible($user, $id)));

                if (count($ids) === 1) {
                    return [$match, $ids[0], [], $part];
                }

                if (count($ids) > 1) {
                    return ['ambiguous', null, $this->named($ids), $part];
                }
            }
        }

        // Last: a project whose name starts with it ("Yunjiao-Guan" -> "Yunjiao-Guan - 61st Ave").
        foreach ($attempts as [$candidate, $part]) {
            $key = $this->norm($candidate);

            if (strlen($key) < 4) {
                continue;
            }

            $ids = collect($index['names'])
                ->filter(fn ($ids, $name) => str_starts_with($name, $key))
                ->flatten()
                ->filter(fn ($id) => $this->visible($user, $id))
                ->values()
                ->all();

            if (count($ids) === 1) {
                return ['partial', $ids[0], [], $part];
            }

            if (count($ids) > 1) {
                return ['ambiguous', null, $this->named($ids), $part];
            }
        }

        return ['none', null, [], $attempts[1][1] ?? null];
    }

    private function projectIndex(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = ['projects' => [], 'names' => [], 'customers' => [], 'codes' => []];

        Project::with('customer:id,first_name,last_name')->get(['id', 'project_name', 'code', 'customer_id'])
            ->each(function (Project $p) use (&$index) {
                $index['projects'][$p->id] = (string) $p->project_name;
                $index['names'][$this->norm($p->project_name)][] = $p->id;

                if ($p->customer) {
                    $index['customers'][$this->norm($p->customer->first_name.$p->customer->last_name)][] = $p->id;
                }

                if (filled($p->code)) {
                    $index['codes'][Str::lower(trim($p->code))] = $p->id;
                }
            });

        return $this->index = $index;
    }

    private function visible(User $user, int $projectId): bool
    {
        // The index already holds only live projects, so a user who sees every
        // project needs no query per project.
        return $this->visibility[$user->id][$projectId] ??= $this->projects->seesEveryProject($user)
            || $this->projects->canAccessProject($user, $projectId);
    }

    private function named(array $ids): array
    {
        return collect($ids)->unique()->mapWithKeys(fn ($id) => [$id => $this->projectIndex()['projects'][$id]])->all();
    }

    private function norm(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower((string) $value));
    }

    /**
     * A milestone's words, in any order: "Storage Milestone 2" (how the import
     * writes it) and "Milestone 2 - Storage" (how older rows were keyed in) are
     * the same milestone for the duplicate check.
     */
    private function milestoneKey(?string $milestone): string
    {
        $words = preg_split('/[^a-z0-9]+/', Str::lower((string) $milestone), -1, PREG_SPLIT_NO_EMPTY);
        sort($words);

        return implode(' ', $words);
    }

    private function duplicateKey(int $projectId, string $payee, string $milestone, float $amount): string
    {
        return implode('|', [$projectId, $payee, $this->milestoneKey($milestone), number_format($amount, 2, '.', '')]);
    }

    private function existing(int $projectId, ?string $payeeKey, string $milestone, float $amount): ?AccountTransaction
    {
        if ($payeeKey === null) {
            return null; // a payee that does not exist yet has no transactions
        }

        return AccountTransaction::where('project_id', $projectId)
            ->where('payee', $payeeKey)
            ->where('amount', $amount)
            ->get(['id', 'milestone', 'transaction_date', 'status'])
            ->first(fn ($t) => $this->milestoneKey($t->milestone) === $this->milestoneKey($milestone));
    }

    /** "$ 9,072.00", "(1,000)", 9072 -> float; anything else -> null. */
    private function amount($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $negative = str_starts_with($text, '(') && str_ends_with($text, ')');
        $clean = str_replace(['$', ',', ' ', '(', ')'], '', $text);

        if (! is_numeric($clean)) {
            return null;
        }

        return $negative ? -abs((float) $clean) : (float) $clean;
    }

    /** Y-m-d, null when empty, false when unreadable. */
    private function date($value): string|null|false
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return is_numeric($value)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString()
                : Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Open / Closed, null when empty, false when unreadable. */
    private function status($value): string|null|false
    {
        $text = Str::lower(trim((string) $value));

        return match (true) {
            $text === '' => null,
            in_array($text, ['open', 'expected', 'pending', 'due', 'unpaid'], true) => AccountTransaction::STATUS_OPEN,
            in_array($text, ['closed', 'paid', 'received', 'done', 'settled'], true) => AccountTransaction::STATUS_CLOSED,
            default => false,
        };
    }

    /** Columns by field when `$values` is a header row, else null. */
    private function headerColumns(array $values): ?array
    {
        $columns = [];

        foreach ($values as $i => $value) {
            $field = self::HEADERS[$this->norm(is_string($value) ? $value : '')] ?? null;

            if ($field && ! isset($columns[$field])) {
                $columns[$field] = $i;
            }
        }

        return count($columns) >= 2 && (isset($columns['project']) || isset($columns['code'])) ? $columns : null;
    }
}
