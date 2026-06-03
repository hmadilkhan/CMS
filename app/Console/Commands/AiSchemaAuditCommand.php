<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audits config/ai_schema.php and config/ai_field_dictionary.php against the
 * live database so the AI assistant's knowledge never silently drifts from the
 * real schema when migrations change.
 *
 * Reports:
 *   - business tables that exist in the DB but are not exposed in ai_schema;
 *   - allowed columns that no longer exist in the DB (typos / renames / drops);
 *   - ai_schema tables whose underlying DB table is missing;
 *   - allowed columns that have no plain-language description in the dictionary.
 *
 * Exit code is non-zero when any drift is found, so it can gate CI/deploys.
 */
class AiSchemaAuditCommand extends Command
{
    protected $signature = 'ai:schema-audit {--json : Output machine-readable JSON}';

    protected $description = 'Audit ai_schema + ai_field_dictionary against the live database';

    /**
     * Framework / infrastructure / secret tables intentionally NOT exposed to AI.
     */
    private const SKIP_TABLES = [
        'migrations', 'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks',
        'sessions', 'password_reset_tokens', 'personal_access_tokens', 'notifications',
        'email_configs', 'imap_accounts',
    ];

    public function handle(): int
    {
        try {
            $dbColumns = $this->liveColumns();
        } catch (\Throwable $e) {
            $this->error('Could not read the database: ' . $e->getMessage());

            return self::FAILURE;
        }

        $dbTables   = array_keys($dbColumns);
        $schema     = (array) config('ai_schema.tables', []);
        $dictionary = (array) config('ai_field_dictionary.tables', []);

        $missingTables   = [];   // in DB, not in ai_schema
        $missingDbTables = [];   // in ai_schema, not in DB (excluding placeholders)
        $badColumns      = [];   // allowed column not in DB
        $undocumented    = [];   // allowed column with no dictionary description

        foreach ($dbTables as $table) {
            if (in_array($table, self::SKIP_TABLES, true) || isset($schema[$table])) {
                continue;
            }
            $missingTables[] = $table;
        }

        foreach ($schema as $table => $cfg) {
            $isPlaceholder = (bool) ($cfg['manual_mapping_required'] ?? false);

            if (! isset($dbColumns[$table])) {
                if (! $isPlaceholder) {
                    $missingDbTables[] = $table;
                }
                continue;
            }

            $live = $dbColumns[$table];

            foreach ((array) ($cfg['allowed_columns'] ?? []) as $column) {
                if (! in_array($column, $live, true)) {
                    $badColumns[] = "{$table}.{$column}";
                }

                $hasDoc = isset($dictionary[$table]['columns'][$column]);
                if (! $hasDoc && in_array($column, $live, true)) {
                    $undocumented[] = "{$table}.{$column}";
                }
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'tables_missing_from_ai_schema' => array_values($missingTables),
                'ai_schema_tables_missing_in_db' => array_values($missingDbTables),
                'allowed_columns_not_in_db' => array_values($badColumns),
                'undocumented_columns' => array_values($undocumented),
            ], JSON_PRETTY_PRINT));

            return $missingTables || $missingDbTables || $badColumns ? self::FAILURE : self::SUCCESS;
        }

        $this->info('AI Schema Audit');
        $this->line(sprintf('DB tables: %d | ai_schema tables: %d | documented tables: %d', count($dbTables), count($schema), count($dictionary)));
        $this->newLine();

        $this->report('Business tables in DB but MISSING from ai_schema', $missingTables, 'error');
        $this->report('ai_schema tables whose DB table is MISSING (typo or not migrated)', $missingDbTables, 'error');
        $this->report('Allowed columns NOT present in DB (typo / renamed / dropped)', $badColumns, 'error');
        $this->report('Allowed columns with no dictionary description', $undocumented, 'warn');

        if (! $missingTables && ! $missingDbTables && ! $badColumns && ! $undocumented) {
            $this->info('✔ Everything is in sync. Nothing missed.');
        }

        // Undocumented columns are a soft warning; only hard drift fails the run.
        return $missingTables || $missingDbTables || $badColumns ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param array<int,string> $items
     */
    private function report(string $title, array $items, string $level): void
    {
        if ($items === []) {
            return;
        }

        $line = $level === 'error' ? fn ($t) => $this->error($t) : fn ($t) => $this->warn($t);
        $line(sprintf('%s (%d):', $title, count($items)));

        foreach ($items as $item) {
            $this->line('  - ' . $item);
        }

        $this->newLine();
    }

    /**
     * @return array<string, array<int, string>> table => [columns]
     */
    private function liveColumns(): array
    {
        $rows = DB::select(
            'SELECT table_name AS tn, column_name AS cn
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             ORDER BY table_name, ordinal_position'
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row->tn][] = $row->cn;
        }

        return $out;
    }
}
