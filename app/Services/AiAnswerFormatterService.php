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
            'message' => (string) ($answer['message'] ?? 'Here are the results.'),
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

        if (($plan['answer_type'] ?? null) === 'count') {
            $count = $rows[0]['aggregate'] ?? 0;

            return [
                'type' => 'count',
                'message' => 'Result found.',
                'columns' => ['Count'],
                'rows' => [['label' => 'Count', 'value' => $count]],
                'cards' => [
                    ['label' => 'Count', 'value' => $count],
                ],
            ];
        }

        $type = ($plan['answer_type'] ?? 'table') === 'card' ? 'card' : 'table';
        $message = match ($plan['intent'] ?? null) {
            'project_department_summary' => 'Here is the project count by department and subdepartment.',
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
