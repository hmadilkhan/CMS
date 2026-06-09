<?php

namespace App\Services;

use Throwable;

class AiAnswerFormatterService
{
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiProfiler $profiler
    ) {
    }

    public function format(string $question, array $plan, array $execution): array
    {
        $this->profiler->stage('formatter');

        if (! ($execution['success'] ?? false)) {
            return $this->safeAnswer('text', $execution['error_message'] ?? 'I could not safely run this CRM query.');
        }

        // 1. Build the answer deterministically (instant, never wrong). Rows,
        //    columns and cards always come straight from the executed query — this
        //    is the same logic that produced correct results while the LLM
        //    formatter was offline, and it costs no OpenAI call.
        $answer = $this->fallbackAnswer($plan, $execution, $question);

        // 2. Reports already carry a rich deterministic summary, and empty results
        //    need no polish → return immediately, no OpenAI round-trip.
        if ($answer['rows'] === [] || $this->hasDeterministicSummary($plan)) {
            return $answer;
        }

        // 3. Otherwise spend ONE cheap call on a friendlier one-line message. Only
        //    the row count + a few sample rows are sent (never the full result
        //    set), so the call stays fast regardless of how many rows came back.
        try {
            $generated = $this->generateMessage($question, $answer);

            if (($generated['message'] ?? '') !== '') {
                $answer['message'] = $generated['message'];
            }

            if (! empty($generated['suggestions'])) {
                $answer['suggestions'] = $generated['suggestions'];
            }
        } catch (Throwable) {
            // Keep the deterministic message on any failure — the answer (rows,
            // columns, cards) is already correct, only the wording is affected.
        }

        return $answer;
    }

    /**
     * Intents whose deterministic summary (totals, highest-profit project, …) is
     * already richer than a one-line LLM message would be, so the OpenAI call is
     * skipped entirely for them.
     */
    private function hasDeterministicSummary(array $plan): bool
    {
        return in_array($plan['intent'] ?? null, [
            'profitability_report', 'profitability_report_by_date_range',
            'forecast_report', 'forecast_report_by_date_range',
            'override_report', 'override_report_by_date_range',
            'transaction_report', 'transaction_report_by_date_range',
        ], true);
    }

    /**
     * Ask the model for ONE short friendly sentence describing the result AND 2-3
     * natural follow-up questions — both in a single call (no extra round-trip).
     * Only a small sample of rows is sent (the full table is rendered separately),
     * and a strict json_schema guarantees a tiny, valid response, so this stays
     * fast and cheap no matter how large the result set is.
     *
     * @return array{message:string,suggestions:array<int,string>}
     */
    private function generateMessage(string $question, array $answer): array
    {
        $rows = $answer['rows'] ?? [];

        $response = $this->openAiService->createJsonResponse(
            'You summarise a CRM query result for a chat UI. Return JSON {"message": "...", "suggestions": ["...", "..."]}. '
            .'"message" is ONE short, friendly sentence (under 25 words) using the provided count and sample exactly — never '
            .'invent numbers or rows, and do not list every row (a table is shown separately). '
            .'"suggestions" is 2-3 SHORT, standalone follow-up questions the user would naturally ask next about this result '
            .'(a useful filter, sort, breakdown, count, or drill-down). Each must make sense on its own and use '
            .'business-meaningful attributes (status, department, location, dates, amounts, counts, top/bottom). '
            .'Do NOT reference technical or internal columns (ids, tokens, image, link, length, slug, raw flags). '
            .'If the user wrote in Roman-Urdu, reply in the same style.',
            [
                'question' => $question,
                'answer_type' => $answer['type'] ?? 'table',
                'row_count' => count($rows),
                'columns' => $answer['columns'] ?? [],
                'sample_rows' => array_slice($rows, 0, 5),
            ],
            300,
            [
                'type' => 'json_schema',
                'name' => 'answer_message',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['message', 'suggestions'],
                    'properties' => [
                        'message' => ['type' => 'string'],
                        'suggestions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
            ]
        );

        $json = is_array($response['json'] ?? null) ? $response['json'] : [];

        $suggestions = array_values(array_filter(
            array_map(fn ($s) => trim((string) $s), (array) ($json['suggestions'] ?? [])),
            fn ($s) => $s !== ''
        ));

        return [
            'message' => trim((string) ($json['message'] ?? '')),
            'suggestions' => array_slice($suggestions, 0, 3),
        ];
    }

    /**
     * Drop noisy created_at / updated_at columns from table output unless the user
     * actually asked about dates/times. Keeps meaningful date columns (e.g.
     * solar_install_date, transaction_date, sold_date) which are not named exactly
     * created_at/updated_at.
     *
     * @param array<int,string> $columns
     * @return array<int,string>
     */
    private function hideTimestampColumns(array $columns, string $question): array
    {
        $q = mb_strtolower($question);

        foreach (['created', 'updated', 'timestamp', 'time stamp', 'when ', 'kab ', 'date added', 'last modified', 'modified'] as $cue) {
            if (str_contains($q, $cue)) {
                return $columns; // user asked about timing — keep them
            }
        }

        return array_values(array_filter(
            $columns,
            fn ($col) => ! in_array(strtolower((string) $col), ['created_at', 'updated_at'], true)
        ));
    }

    private function fallbackAnswer(array $plan, array $execution, string $question = ''): array
    {
        $rows = $execution['rows'] ?? [];
        $rows = $this->appendTotalRowIfNeeded($plan, $rows);
        $rows = $this->formatRowsForDisplay($rows);

        if (($plan['answer_type'] ?? null) === 'count') {
            $count = $this->extractCountValue($rows[0] ?? []);
            $label = ($plan['intent'] ?? null) === 'project_acceptance_count'
                ? 'Projects'
                : 'Count';
            $message = ($plan['intent'] ?? null) === 'project_acceptance_count'
                ? 'Here is the project acceptance count.'
                : 'Result found.';

            return [
                'type' => 'count',
                'message' => $message,
                'columns' => ['label', 'value'],
                'rows' => [['label' => $label, 'value' => $count]],
                'cards' => [
                    ['label' => $label, 'value' => $count],
                ],
            ];
        }

        $type = ($plan['answer_type'] ?? 'table') === 'card' ? 'card' : 'table';
        $message = $this->summaryMessage($plan, $rows) ?? match ($plan['intent'] ?? null) {
            'project_department_summary' => in_array('sub_departments', $plan['tables'] ?? [], true)
                ? 'Here is the project count by department and subdepartment.'
                : 'Here is the project count by department.',
            'user_role_count' => 'Here is the user count by role.',
            'user_role_list' => 'Here is the user list by role.',
            'project_acceptance_summary' => 'Here is the project acceptance summary.',
            'project_acceptance_count' => 'Here is the project acceptance count.',
            'project_acceptance_list' => 'Here are the matching projects by acceptance status.',
            'project_financing_summary' => 'Here is the project financing summary.',
            'forecast_report', 'forecast_report_by_date_range' => 'Here is the forecast report.',
            'override_report', 'override_report_by_date_range' => 'Here is the override report.',
            'transaction_report', 'transaction_report_by_date_range' => 'Here is the transaction report.',
            'profitability_report', 'profitability_report_by_date_range' => 'Here is the profitability report.',
            'ticket_creator_status_summary' => 'Here is the ticket status summary by user.',
            default => 'Here are the matching CRM results.',
        };

        return [
            'type' => $type,
            'message' => $message,
            'columns' => $this->hideTimestampColumns($this->columnsFromRows($rows), $question),
            'rows' => $rows,
            'cards' => $type === 'card' ? $this->cardsFromRows($rows) : [],
        ];
    }

    private function safeAnswer(string $type, string $message): array
    {
        return [
            'type' => $type,
            'message' => $message,
            'columns' => [],
            'rows' => [],
            'cards' => [],
        ];
    }

    private function columnsFromRows(array $rows): array
    {
        return isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];
    }

    /**
     * Pull the numeric count out of a count query's single row, regardless of how
     * the column was aliased. The structured builder aliases it `aggregate`, but
     * Text-to-SQL picks arbitrary names (project_count, total, count, …); reading a
     * single fixed key returned 0 and wrongly reported "no records" for a non-zero
     * count.
     */
    private function extractCountValue(array $row): int|float
    {
        foreach (['aggregate', 'value', 'count', 'total'] as $key) {
            if (isset($row[$key]) && is_numeric($row[$key])) {
                return $row[$key] + 0;
            }
        }

        foreach ($row as $key => $val) {
            if (is_numeric($val) && preg_match('/count|total|aggregate|qty|number|sum/i', (string) $key)) {
                return $val + 0;
            }
        }

        // A COUNT query returns a single numeric column — fall back to the first.
        foreach ($row as $val) {
            if (is_numeric($val)) {
                return $val + 0;
            }
        }

        return 0;
    }

    private function appendTotalRowIfNeeded(array $plan, array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        if (in_array($plan['intent'] ?? null, ['profitability_report', 'profitability_report_by_date_range'], true)) {
            return $this->appendProfitabilityTotalRow($rows);
        }

        if (in_array($plan['intent'] ?? null, ['forecast_report', 'forecast_report_by_date_range'], true)) {
            return $this->appendForecastTotalRow($rows);
        }

        if (in_array($plan['intent'] ?? null, ['override_report', 'override_report_by_date_range'], true)) {
            return $this->appendOverrideTotalRow($rows);
        }

        if (in_array($plan['intent'] ?? null, ['transaction_report', 'transaction_report_by_date_range'], true)) {
            return $this->appendTransactionTotalRow($rows);
        }

        if (($plan['intent'] ?? null) !== 'project_department_summary') {
            return $rows;
        }

        $total = collect($rows)->sum(fn (array $row) => (int) ($row['aggregate'] ?? 0));
        $totalRow = ['department_name' => 'Total'];

        if (in_array('sub_departments', $plan['tables'] ?? [], true)) {
            $totalRow['sub_department_name'] = '';
        }

        $totalRow['aggregate'] = $total;
        $rows[] = $totalRow;

        return $rows;
    }

    private function appendProfitabilityTotalRow(array $rows): array
    {
        $sumColumns = [
            'contract_amount',
            'dealer_fee_amount',
            'redline_costs',
            'adders',
            'commission',
            'actual_material_cost',
            'actual_labor_cost',
            'actual_permit_fee',
            'office_cost',
            'actual_job_cost',
            'profit_amount',
        ];

        $totalRow = array_fill_keys(array_keys($rows[0] ?? []), '');
        $totalRow['first_name'] = 'Total';

        foreach ($sumColumns as $column) {
            if (! array_key_exists($column, $totalRow)) {
                continue;
            }

            $totalRow[$column] = round(collect($rows)->sum(fn (array $row) => $this->numericValue($row[$column] ?? 0)), 2);
        }

        if (array_key_exists('profit_margin_percent', $totalRow)) {
            $basis = $this->numericValue($totalRow['redline_costs'] ?? 0) + $this->numericValue($totalRow['adders'] ?? 0);
            $totalRow['profit_margin_percent'] = $basis > 0
                ? round(($this->numericValue($totalRow['profit_amount'] ?? 0) / $basis) * 100, 2)
                : null;
        }

        $rows[] = $totalRow;

        return $rows;
    }

    private function appendForecastTotalRow(array $rows): array
    {
        $sumColumns = [
            'contract_amount',
            'commission',
            'dealer_fee_amount',
            'net_sales',
        ];

        $totalRow = array_fill_keys(array_keys($rows[0] ?? []), '');
        $totalRow['sold_date'] = 'Total';

        foreach ($sumColumns as $column) {
            if (! array_key_exists($column, $totalRow)) {
                continue;
            }

            $totalRow[$column] = round(collect($rows)->sum(fn (array $row) => $this->numericValue($row[$column] ?? 0)), 2);
        }

        $rows[] = $totalRow;

        return $rows;
    }

    private function appendOverrideTotalRow(array $rows): array
    {
        $sumColumns = [
            'redline_costs',
            'panel_qty',
            'overwrite_base_price',
            'overwrite_panel_price',
            'total_override_panel_cost',
            'total_override_cost',
            'actual_redline_cost',
        ];

        $totalRow = array_fill_keys(array_keys($rows[0] ?? []), '');
        $totalRow['sold_date'] = 'Total';

        foreach ($sumColumns as $column) {
            if (! array_key_exists($column, $totalRow)) {
                continue;
            }

            $totalRow[$column] = round(collect($rows)->sum(fn (array $row) => $this->numericValue($row[$column] ?? 0)), 2);
        }

        $rows[] = $totalRow;

        return $rows;
    }

    private function appendTransactionTotalRow(array $rows): array
    {
        $sumColumns = [
            'amount',
            'deduction_amount',
            'remitted_amount',
        ];

        $totalRow = array_fill_keys(array_keys($rows[0] ?? []), '');
        $totalRow['project_name'] = 'Total';

        foreach ($sumColumns as $column) {
            if (! array_key_exists($column, $totalRow)) {
                continue;
            }

            $totalRow[$column] = round(collect($rows)->sum(fn (array $row) => $this->numericValue($row[$column] ?? 0)), 2);
        }

        $rows[] = $totalRow;

        return $rows;
    }

    private function summaryMessage(array $plan, array $rows): ?string
    {
        if (in_array($plan['intent'] ?? null, ['forecast_report', 'forecast_report_by_date_range'], true)) {
            return $this->forecastSummaryMessage($plan, $rows);
        }

        if (in_array($plan['intent'] ?? null, ['override_report', 'override_report_by_date_range'], true)) {
            return $this->overrideSummaryMessage($plan, $rows);
        }

        if (in_array($plan['intent'] ?? null, ['transaction_report', 'transaction_report_by_date_range'], true)) {
            return $this->transactionSummaryMessage($plan, $rows);
        }

        if (! in_array($plan['intent'] ?? null, ['profitability_report', 'profitability_report_by_date_range'], true) || $rows === []) {
            return null;
        }

        $totalRow = collect($rows)->first(fn (array $row) => ($row['first_name'] ?? null) === 'Total');
        $dataRows = collect($rows)->reject(fn (array $row) => ($row['first_name'] ?? null) === 'Total');
        $highest = $dataRows
            ->sortByDesc(fn (array $row) => $this->numericValue($row['profit_amount'] ?? 0))
            ->first();

        $parts = ['Here is the profitability report with the full table below.'];

        if (is_array($totalRow)) {
            $parts[] = 'Total contract amount is ' . $this->formatCurrency($totalRow['contract_amount'] ?? 0)
                . ', total profit is ' . $this->formatCurrency($totalRow['profit_amount'] ?? 0)
                . ', and overall profit margin is ' . $this->formatPercent($totalRow['profit_margin_percent'] ?? 0) . '.';
        }

        if (is_array($highest)) {
            $project = trim((string) ($highest['project_name'] ?? ''));
            $code = trim((string) ($highest['code'] ?? ''));
            $label = $project !== '' ? $project : trim((string) (($highest['first_name'] ?? '') . ' ' . ($highest['last_name'] ?? '')));

            if ($label !== '') {
                $parts[] = 'Highest profit project is ' . $label
                    . ($code !== '' ? ' (' . $code . ')' : '')
                    . ' with ' . $this->formatCurrency($highest['profit_amount'] ?? 0)
                    . ' profit and ' . $this->formatPercent($highest['profit_margin_percent'] ?? 0) . ' margin.';
            }
        }

        return implode(' ', $parts);
    }

    private function forecastSummaryMessage(array $plan, array $rows): ?string
    {
        if (! in_array($plan['intent'] ?? null, ['forecast_report', 'forecast_report_by_date_range'], true) || $rows === []) {
            return null;
        }

        $totalRow = collect($rows)->first(fn (array $row) => ($row['sold_date'] ?? null) === 'Total');

        if (! is_array($totalRow)) {
            return 'Here is the forecast report with the full table below.';
        }

        return 'Here is the forecast report with the full table below. Total contract amount is '
            . $this->formatCurrency($totalRow['contract_amount'] ?? 0)
            . ', total commission is ' . $this->formatCurrency($totalRow['commission'] ?? 0)
            . ', total dealer fee is ' . $this->formatCurrency($totalRow['dealer_fee_amount'] ?? 0)
            . ', and total net sales is ' . $this->formatCurrency($totalRow['net_sales'] ?? 0) . '.';
    }

    private function overrideSummaryMessage(array $plan, array $rows): ?string
    {
        if (! in_array($plan['intent'] ?? null, ['override_report', 'override_report_by_date_range'], true) || $rows === []) {
            return null;
        }

        $totalRow = collect($rows)->first(fn (array $row) => ($row['sold_date'] ?? null) === 'Total');

        if (! is_array($totalRow)) {
            return 'Here is the override report with the full table below.';
        }

        return 'Here is the override report with the full table below. Total redline cost is '
            . $this->formatCurrency($totalRow['redline_costs'] ?? 0)
            . ', total override cost is ' . $this->formatCurrency($totalRow['total_override_cost'] ?? 0)
            . ', and total actual redline cost is ' . $this->formatCurrency($totalRow['actual_redline_cost'] ?? 0) . '.';
    }

    private function transactionSummaryMessage(array $plan, array $rows): ?string
    {
        if (! in_array($plan['intent'] ?? null, ['transaction_report', 'transaction_report_by_date_range'], true) || $rows === []) {
            return null;
        }

        $totalRow = collect($rows)->first(fn (array $row) => ($row['project_name'] ?? null) === 'Total');

        if (! is_array($totalRow)) {
            return 'Here is the transaction report with the full table below.';
        }

        return 'Here is the transaction report with the full table below. Total amount is '
            . $this->formatCurrency($totalRow['amount'] ?? 0)
            . ', total deduction is ' . $this->formatCurrency($totalRow['deduction_amount'] ?? 0)
            . ', and total remitted amount is ' . $this->formatCurrency($totalRow['remitted_amount'] ?? 0) . '.';
    }

    private function numericValue(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = preg_replace('/[^0-9.\-]/', '', $value);

            return is_numeric($clean) ? (float) $clean : 0.0;
        }

        return 0.0;
    }

    private function formatCurrency(mixed $value): string
    {
        return '$' . number_format($this->numericValue($value), 2);
    }

    private function formatPercent(mixed $value): string
    {
        return number_format($this->numericValue($value), 2) . '%';
    }

    private function formatRowsForDisplay(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row) {
                foreach ($row as $column => $value) {
                    if ($this->isCurrencyColumn((string) $column) && $this->isDisplayNumeric($value)) {
                        $row[$column] = $this->formatCurrency($value);
                        continue;
                    }

                    if ($this->isPercentColumn((string) $column) && $this->isDisplayNumeric($value)) {
                        $row[$column] = $this->formatPercent($value);
                    }
                }

                return $row;
            })
            ->values()
            ->all();
    }

    private function isCurrencyColumn(string $column): bool
    {
        if ($this->isPercentColumn($column)) {
            return false;
        }

        return str_contains($column, 'amount')
            || str_contains($column, 'cost')
            || str_contains($column, 'price')
            || str_contains($column, 'fee')
            || str_contains($column, 'commission')
            || str_contains($column, 'revenue')
            || str_contains($column, 'expense')
            || str_contains($column, 'profit')
            || str_contains($column, 'sales')
            || str_contains($column, 'margin')
            || in_array($column, ['adders', 'redline_costs', 'customer_portion', 'holdback_amount'], true);
    }

    private function isPercentColumn(string $column): bool
    {
        return str_contains($column, 'percent') || str_ends_with($column, '_percentage');
    }

    private function isDisplayNumeric(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        $clean = preg_replace('/[$,%\s,]/', '', $value);

        return is_numeric($clean);
    }

    private function cardsFromRows(array $rows): array
    {
        return collect($rows)
            ->take(6)
            ->flatMap(function (array $row) {
                return collect($row)
                    ->map(fn ($value, $label) => [
                        'label' => ucwords(str_replace('_', ' ', (string) $label)),
                        'value' => $value,
                    ]);
            })
            ->values()
            ->all();
    }

}
