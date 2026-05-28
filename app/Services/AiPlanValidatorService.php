<?php

namespace App\Services;

use App\Models\User;

class AiPlanValidatorService
{
    private const MODES = ['fixed_action', 'data_explorer', 'clarification_required', 'unsupported'];

    private const OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'like', 'in', 'between'];

    private const BLOCKED_WORDS = ['insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate', 'union', '--', '/*', '*/', ';'];

    public function __construct(
        private readonly AiSchemaService $schemaService,
        private readonly AiAccessPolicyService $accessPolicyService
    ) {
    }

    public function validate(array $plan, User $user): array
    {
        if ($this->containsRawSql($plan)) {
            return $this->reject('Raw SQL is not allowed in AI query plans.');
        }

        $mode = (string) ($plan['mode'] ?? 'data_explorer');

        if (! in_array($mode, self::MODES, true)) {
            return $this->reject('The AI returned an unsupported planning mode.');
        }

        if (in_array($mode, ['clarification_required', 'unsupported'], true) || ($plan['intent'] ?? null) === 'unknown') {
            return $this->approve('no_query');
        }

        if ((float) ($plan['confidence'] ?? 1) < 0.65) {
            return $this->reject('This CRM question needs clarification before I can answer safely.');
        }

        if ((int) ($plan['limit'] ?? 100) > 100) {
            return $this->reject('The requested query limit is too high.');
        }

        foreach (($plan['tables'] ?? []) as $table) {
            if (! is_string($table) || ! $this->schemaService->isTableAllowed($table)) {
                return $this->reject('The plan references a CRM table that is not allowed.');
            }

            if (! $this->accessPolicyService->canAccessTable($user, $table)) {
                return $this->reject('You do not have permission to access this information.');
            }

            if (! $this->schemaService->getAccessRule($table)) {
                return $this->reject('The plan references a table without an access rule.');
            }
        }

        foreach (($plan['columns'] ?? []) as $column) {
            if (! $this->columnAllowedForAnyTable($user, $plan, (string) $column)) {
                return $this->reject('The plan references a CRM column that is not allowed.');
            }
        }

        foreach (($plan['group_by'] ?? []) as $column) {
            if (! $this->columnAllowedForAnyTable($user, $plan, (string) $column)) {
                return $this->reject('The plan groups by a CRM column that is not allowed.');
            }
        }

        foreach (($plan['filters'] ?? []) as $filter) {
            if (! is_array($filter)) {
                return $this->reject('The plan contains an invalid filter.');
            }

            $operator = strtolower((string) ($filter['operator'] ?? '='));
            $column = (string) ($filter['column'] ?? '');
            $table = (string) ($filter['table'] ?? '');

            if (! in_array($operator, self::OPERATORS, true)) {
                return $this->reject('The plan uses a filter operator that is not allowed.');
            }

            if ($table !== '' && (! in_array($table, $plan['tables'] ?? [], true) || ! $this->accessPolicyService->canAccessColumn($user, $table, $column))) {
                return $this->reject('The plan filters on a CRM column that is not allowed.');
            }

            if ($table === '' && $column !== '' && ! $this->columnAllowedForAnyTable($user, $plan, $column)) {
                return $this->reject('The plan filters on a CRM column that is not allowed.');
            }
        }

        foreach (($plan['relationships'] ?? []) as $relationship) {
            if (! is_array($relationship) || ! $this->relationshipAllowed((string) ($relationship['from'] ?? ''), (string) ($relationship['to'] ?? ''))) {
                return $this->reject('The plan references a CRM relationship that is not allowed.');
            }
        }

        foreach (($plan['sort'] ?? []) as $sort) {
            if (! is_array($sort)) {
                return $this->reject('The plan contains an invalid sort.');
            }

            $table = (string) ($sort['table'] ?? '');
            $column = (string) ($sort['column'] ?? '');
            $direction = strtolower((string) ($sort['direction'] ?? 'asc'));

            if (! in_array($direction, ['asc', 'desc'], true) || ! $this->accessPolicyService->canAccessColumn($user, $table, $column)) {
                return $this->reject('The plan sorts by a CRM column that is not allowed.');
            }
        }

        if (($plan['requires_finance_access'] ?? false) && ! $this->accessPolicyService->canAccessFinance($user)) {
            return $this->reject('You do not have permission to access this information.');
        }

        return $this->approve('approved');
    }

    private function columnAllowedForAnyTable(User $user, array $plan, string $column): bool
    {
        if ($column === 'aggregate') {
            return true;
        }

        foreach (($plan['tables'] ?? []) as $table) {
            if ($this->schemaService->isSensitiveColumn($table, $column)) {
                $rule = $this->schemaService->getAccessRule($table);

                return $rule === 'profitability_access'
                    ? $this->accessPolicyService->canAccessProfitability($user)
                    : $this->accessPolicyService->canAccessFinance($user);
            }

            if ($this->accessPolicyService->canAccessColumn($user, $table, $column)) {
                return true;
            }
        }

        return false;
    }

    private function relationshipAllowed(string $from, string $to): bool
    {
        [$fromTable, $fromColumn] = array_pad(explode('.', $from, 2), 2, null);
        [$toTable, $toColumn] = array_pad(explode('.', $to, 2), 2, null);

        if (! $fromTable || ! $fromColumn || ! $toTable || ! $toColumn) {
            return false;
        }

        foreach ($this->schemaService->getRelationships($fromTable) as $relationship) {
            if (($relationship['table'] ?? null) === $toTable
                && ($relationship['local_key'] ?? null) === $fromColumn
                && ($relationship['foreign_key'] ?? null) === $toColumn) {
                return true;
            }
        }

        foreach ($this->schemaService->getRelationships($toTable) as $relationship) {
            if (($relationship['table'] ?? null) === $fromTable
                && ($relationship['local_key'] ?? null) === $toColumn
                && ($relationship['foreign_key'] ?? null) === $fromColumn) {
                return true;
            }
        }

        return false;
    }

    private function containsRawSql(array $plan): bool
    {
        if (! is_null($plan['sql'] ?? null)) {
            return true;
        }

        return $this->containsBlockedStringValue($plan);
    }

    private function containsBlockedStringValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsBlockedStringValue($item)) {
                    return true;
                }
            }

            return false;
        }

        if (! is_string($value)) {
            return false;
        }

        foreach (self::BLOCKED_WORDS as $word) {
            if (in_array($word, ['--', '/*', '*/', ';'], true) && str_contains($value, $word)) {
                return true;
            }

            if (! in_array($word, ['--', '/*', '*/', ';'], true) && preg_match('/\b' . preg_quote($word, '/') . '\b/i', $value)) {
                return true;
            }
        }

        return false;
    }

    private function approve(string $status): array
    {
        return ['approved' => true, 'status' => $status, 'reason' => null];
    }

    private function reject(string $reason): array
    {
        return ['approved' => false, 'status' => 'rejected', 'reason' => $reason];
    }
}
