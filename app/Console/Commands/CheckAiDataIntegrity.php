<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckAiDataIntegrity extends Command
{
    protected $signature = 'ai:check-integrity';

    protected $description = 'Check important CRM table counts for AI shared-hosting monitoring.';

    public function handle(): int
    {
        $tables = array_values(array_filter((array) config('ai.schema.integrity_tables', [])));
        $counts = [];
        $changes = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $counts[$table] = DB::table($table)->count();
        }

        $previousCounts = Cache::get('ai_integrity_counts', []);

        foreach ($counts as $table => $count) {
            if (! array_key_exists($table, $previousCounts)) {
                continue;
            }

            $diff = $count - (int) $previousCounts[$table];

            if ($diff !== 0) {
                $changes[$table] = $diff;
            }
        }

        if ($changes !== []) {
            Log::warning('AI integrity monitor detected CRM row count changes.', [
                'changes' => $changes,
                'current_counts' => $counts,
            ]);
        }

        Cache::put('ai_integrity_counts', $counts, now()->addDays(2));

        $this->info('AI integrity check complete. Changes detected: ' . count($changes));

        return self::SUCCESS;
    }
}
