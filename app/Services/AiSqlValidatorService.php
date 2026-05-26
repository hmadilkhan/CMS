<?php

namespace App\Services;

use App\Models\User;

class AiSqlValidatorService
{
    private const BLOCKED_KEYWORDS = [
        'insert',
        'update',
        'delete',
        'drop',
        'alter',
        'create',
        'truncate',
        'replace',
        'grant',
        'revoke',
        'exec',
        'execute',
    ];

    public function __construct(
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService
    ) {
    }

    public function validate(array $sqlPreview, array $plan, User $user): array
    {
        // Security: only SELECT statements generated from approved schema metadata may pass this validator.
        $sql = (string) ($sqlPreview['sql'] ?? '');
        $normalizedSql = strtolower(trim($sql));

        if ($normalizedSql === '' || ! str_starts_with($normalizedSql, 'select')) {
            return $this->reject('Only SELECT queries are allowed.');
        }

        foreach (self::BLOCKED_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
                return $this->reject('This query contains a blocked SQL operation.');
            }
        }

        if (str_contains($sql, '--') || str_contains($sql, '/*') || str_contains($sql, '*/')) {
            return $this->reject('SQL comments are not allowed.');
        }

        if ($this->hasMultipleStatements($sql)) {
            return $this->reject('Multiple SQL statements are not allowed.');
        }

        if (preg_match('/\bunion\b/i', $sql)) {
            return $this->reject('UNION queries are not allowed.');
        }

        $limit = $sqlPreview['limit'] ?? null;

        if (! is_int($limit) || $limit < 1 || $limit > 100 || ! preg_match('/\blimit\s+100\b/i', $sql)) {
            return $this->reject('A LIMIT of 100 or less is required.');
        }

        foreach ($sqlPreview['tables'] ?? [] as $table) {
            if (! $this->aiSchemaService->isTableAllowed($table)) {
                return $this->reject('The query references a table that is not allowed.');
            }

            if (! $this->aiPermissionService->canAccessTable($user, $table)) {
                return $this->reject('You do not have permission to access this information.');
            }
        }

        foreach (($plan['columns'] ?? []) as $column) {
            if (! $this->isColumnAllowedForAnyPlanTable($user, $plan, $column)) {
                return $this->reject('The query references a column that is not allowed.');
            }
        }

        foreach (($plan['filters'] ?? []) as $filter) {
            $column = is_array($filter) ? ($filter['column'] ?? null) : null;

            if ($column && ! $this->isColumnAllowedForAnyPlanTable($user, $plan, $column)) {
                return $this->reject('The query filters on a column that is not allowed.');
            }
        }

        if (($plan['requires_finance_access'] ?? false) && ! $this->aiPermissionService->canAccessFinance($user)) {
            return $this->reject('You do not have permission to access this information.');
        }

        foreach (($plan['tables'] ?? []) as $table) {
            $accessRule = $this->aiSchemaService->getAccessRule($table);

            if ($accessRule === 'finance_access' && ! $this->aiPermissionService->canAccessFinance($user)) {
                return $this->reject('You do not have permission to access this information.');
            }

            if ($accessRule === 'profitability_access' && ! $this->aiPermissionService->canAccessProfitability($user)) {
                return $this->reject('You do not have permission to access this information.');
            }
        }

        return [
            'approved' => true,
            'status' => 'approved',
            'reason' => null,
        ];
    }

    private function reject(string $reason): array
    {
        return [
            'approved' => false,
            'status' => 'rejected',
            'reason' => $reason,
        ];
    }

    private function hasMultipleStatements(string $sql): bool
    {
        return str_contains(rtrim($sql), ';');
    }

    private function isColumnAllowedForAnyPlanTable(User $user, array $plan, string $column): bool
    {
        if (in_array($column, ['aggregate'], true)) {
            return true;
        }

        foreach (($plan['tables'] ?? []) as $table) {
            if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                return true;
            }
        }

        return false;
    }
}
