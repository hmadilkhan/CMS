<?php

namespace App\Services;

use Throwable;

class AiAnswerFormatterService
{
    public function __construct(private readonly OpenAiService $openAiService)
    {
    }

    public function format(string $question, array $plan, array $execution): array
    {
        if (! ($execution['success'] ?? false)) {
            return $this->safeAnswer('text', $execution['error_message'] ?? 'I could not safely run this CRM query.');
        }

        try {
            $response = $this->openAiService->createJsonResponse(
                $this->instructions(),
                [
                    'question' => $question,
                    'query_plan' => $plan,
                    'rows' => $execution['rows'] ?? [],
                    'row_count' => $execution['row_count'] ?? 0,
                    'required_format' => $this->emptyAnswer($plan['answer_type'] ?? 'text'),
                    'examples' => $this->examples(),
                ],
                1600
            );

            return $this->normalizeAnswer($response['json'], $plan, $execution);
        } catch (Throwable) {
            return $this->fallbackAnswer($plan, $execution);
        }
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
You format CRM query results for a professional CRM chat UI.
Return JSON only. Do not include SQL. Do not invent rows or totals.
Use the result rows exactly as provided.
Keep the message concise and useful for CRM users.
PROMPT;
    }

    private function normalizeAnswer(array $answer, array $plan, array $execution): array
    {
        $type = in_array($answer['type'] ?? null, ['text', 'table', 'card', 'count'], true)
            ? $answer['type']
            : ($plan['answer_type'] ?? 'text');

        $executionRows = array_values($execution['rows'] ?? []);
        $rows = $type === 'card'
            ? array_values($answer['rows'] ?? $executionRows)
            : $executionRows;
        $rows = $this->appendTotalRowIfNeeded($plan, $rows);
        $message = $this->summaryMessage($plan, $rows)
            ?? (string) ($answer['message'] ?? 'Here are the results.');
        $rows = $this->formatRowsForDisplay($rows);
        $cards = array_values($answer['cards'] ?? []);

        if ($type === 'count' && empty($cards)) {
            $cards = [
                [
                    'label' => 'Count',
                    'value' => $rows[0]['value'] ?? $rows[0]['aggregate'] ?? 0,
                ],
            ];
        }

        return [
            'type' => $type,
            'message' => $message,
            'columns' => $type === 'card'
                ? array_values($answer['columns'] ?? $this->columnsFromRows($executionRows))
                : $this->columnsFromRows($executionRows),
            'rows' => $rows,
            'cards' => $cards,
        ];
    }

    private function fallbackAnswer(array $plan, array $execution): array
    {
        $rows = $execution['rows'] ?? [];
        $rows = $this->appendTotalRowIfNeeded($plan, $rows);
        $rows = $this->formatRowsForDisplay($rows);

        if (($plan['answer_type'] ?? null) === 'count') {
            $count = $rows[0]['aggregate'] ?? 0;
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
            'columns' => $this->columnsFromRows($rows),
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

    private function emptyAnswer(string $type): array
    {
        return [
            'type' => $type,
            'message' => '',
            'columns' => [],
            'rows' => [],
            'cards' => [],
        ];
    }

    private function columnsFromRows(array $rows): array
    {
        return isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];
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

    private function examples(): array
    {
        return [
            'assigned_projects_count' => [
                'type' => 'count',
                'message' => 'You have 12 assigned projects.',
                'columns' => ['label', 'value'],
                'rows' => [['label' => 'Assigned Projects', 'value' => 12]],
                'cards' => [['label' => 'Assigned Projects', 'value' => 12]],
            ],
            'projects_by_status' => [
                'type' => 'table',
                'message' => 'Here is your project count by status.',
                'columns' => ['status', 'aggregate'],
                'rows' => [['status' => 'In-Progress', 'aggregate' => 5]],
                'cards' => [],
            ],
            'projects_by_department' => [
                'type' => 'table',
                'message' => 'Here is the project count by department and subdepartment.',
                'columns' => ['department_name', 'sub_department_name', 'aggregate'],
                'rows' => [['department_name' => 'Permitting', 'sub_department_name' => 'Review', 'aggregate' => 12]],
                'cards' => [],
            ],
            'finance_summary' => [
                'type' => 'card',
                'message' => 'Here is the project financing summary.',
                'columns' => [],
                'rows' => [],
                'cards' => [
                    ['label' => 'Financing Status', 'value' => 'Approved'],
                    ['label' => 'Contract Amount', 'value' => '$25,000'],
                ],
            ],
            'ticket_creator_status_summary' => [
                'type' => 'table',
                'message' => 'Here is the ticket status summary by user.',
                'columns' => ['user_name', 'pending_count', 'resolved_count', 'total_tickets'],
                'rows' => [
                    ['user_name' => 'Jane Doe', 'pending_count' => 3, 'resolved_count' => 7, 'total_tickets' => 10],
                ],
                'cards' => [],
            ],
            'profitability_report' => [
                'type' => 'table',
                'message' => 'Here is the profitability report.',
                'columns' => ['project_name', 'total_revenue', 'total_expense', 'gross_profit', 'margin_percent'],
                'rows' => [
                    ['project_name' => 'Project A', 'total_revenue' => 50000, 'total_expense' => 35000, 'gross_profit' => 15000, 'margin_percent' => 30],
                ],
                'cards' => [],
            ],
            'customer_revenue' => [
                'type' => 'table',
                'message' => 'Here is customer-wise revenue.',
                'columns' => ['first_name', 'last_name', 'revenue_amount'],
                'rows' => [
                    ['first_name' => 'Jane', 'last_name' => 'Doe', 'revenue_amount' => 50000],
                ],
                'cards' => [],
            ],
        ];
    }

}
