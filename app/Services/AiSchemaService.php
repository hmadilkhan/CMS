<?php

namespace App\Services;

use Throwable;

class AiSchemaService
{
    public function getAllowedTables(): array
    {
        try {
            return array_keys($this->tables());
        } catch (Throwable) {
            return [];
        }
    }

    public function getTableConfig(string $table): array
    {
        try {
            return $this->tables()[$table] ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    public function getAllowedColumns(string $table): array
    {
        try {
            return $this->getTableConfig($table)['allowed_columns'] ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    public function getSearchableColumns(string $table): array
    {
        try {
            return $this->getTableConfig($table)['searchable_columns'] ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    public function isTableAllowed(string $table): bool
    {
        try {
            return array_key_exists($table, $this->tables());
        } catch (Throwable) {
            return false;
        }
    }

    public function isColumnAllowed(string $table, string $column): bool
    {
        try {
            return in_array($column, $this->getAllowedColumns($table), true);
        } catch (Throwable) {
            return false;
        }
    }

    public function getRelationships(?string $table = null): array
    {
        try {
            if ($table) {
                return $this->getTableConfig($table)['relationships'] ?? [];
            }

            return collect($this->tables())
                ->mapWithKeys(fn (array $config, string $tableName) => [
                    $tableName => $config['relationships'] ?? [],
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    public function isSensitiveColumn(string $table, string $column): bool
    {
        try {
            return in_array($column, $this->getTableConfig($table)['sensitive_columns'] ?? [], true);
        } catch (Throwable) {
            return false;
        }
    }

    public function getAccessRule(string $table): string
    {
        try {
            return $this->getTableConfig($table)['access_rule'] ?? 'admin_only';
        } catch (Throwable) {
            return 'admin_only';
        }
    }

    public function getModelForTable(string $table): ?string
    {
        try {
            $model = $this->getTableConfig($table)['model'] ?? null;

            return is_string($model) ? $model : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function tables(): array
    {
        $tables = config('ai_schema.tables', []);

        return is_array($tables) ? $tables : [];
    }
}
