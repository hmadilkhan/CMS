<?php

namespace App\Services;

class AiSqlParserService
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
        'union',
        'intersect',
        'except',
        'load_file',
        'outfile',
        'sleep',
        'benchmark',
        'information_schema',
        'procedure',
        'function',
        'trigger',
        'event',
        'lock',
        'unlock',
    ];

    public function validate(string $sql): array
    {
        $normalized = strtolower(trim($sql));

        if ($normalized === '' || ! str_starts_with($normalized, 'select')) {
            return $this->reject('Only SELECT queries are allowed.');
        }

        if (str_contains($sql, '--') || str_contains($sql, '/*') || str_contains($sql, '*/')) {
            return $this->reject('SQL comments are not allowed.');
        }

        if ($this->hasMultipleStatements($sql)) {
            return $this->reject('Multiple SQL statements are not allowed.');
        }

        $sqlWithoutStrings = preg_replace("/'([^'\\\\]|\\\\.)*'|\"([^\"\\\\]|\\\\.)*\"/", '', $normalized) ?: $normalized;
        preg_match_all('/[a-z_]+/i', $sqlWithoutStrings, $matches);

        foreach ($matches[0] ?? [] as $token) {
            if (in_array(strtolower($token), self::BLOCKED_KEYWORDS, true)) {
                return $this->reject('This query contains a blocked SQL operation.');
            }
        }

        $tables = $this->extractTables($sql);
        foreach ($tables as $table) {
            if (! $this->isAllowedTable($table)) {
                return $this->reject('The query references a table that is not allowed.');
            }
        }

        return [
            'valid' => true,
            'tables' => $tables,
            'error' => null,
        ];
    }

    private function extractTables(string $sql): array
    {
        preg_match_all('/\b(?:from|join)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private function isAllowedTable(string $table): bool
    {
        return array_key_exists($table, config('ai_schema.tables', []));
    }

    private function hasMultipleStatements(string $sql): bool
    {
        // Strip string literals first so a semicolon inside a value (e.g. a
        // customer name like 'Smith; Inc') is not mistaken for a statement
        // separator and the whole valid query rejected.
        $stripped = preg_replace("/'([^'\\\\]|\\\\.)*'|\"([^\"\\\\]|\\\\.)*\"/", '', $sql) ?? $sql;
        $trimmed = trim($stripped);

        return str_contains(rtrim($trimmed, ';'), ';');
    }

    private function reject(string $error): array
    {
        return [
            'valid' => false,
            'tables' => [],
            'error' => $error,
        ];
    }
}
