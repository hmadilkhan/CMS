<?php

namespace App\Console\Commands;

use App\Models\AiQueryLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only aggregation of the Phase-0 profiling data captured in ai_query_logs
 * (see config('ai.profiling.enabled') + AiProfiler). Tells us where time and
 * tokens actually go before we optimise anything.
 *
 *   php artisan ai:profile-report                 # last 7 days, table output
 *   php artisan ai:profile-report --days=30
 *   php artisan ai:profile-report --json          # machine-readable
 *
 * It only reads — it never changes data or behaviour.
 */
class AiProfileReportCommand extends Command
{
    protected $signature = 'ai:profile-report {--days=7 : Look back this many days} {--json : Output machine-readable JSON} {--limit=5000 : Max rows to sample}';

    protected $description = 'Aggregate AI chat profiling data (latency / round-trips / engine mix) from ai_query_logs';

    public function handle(): int
    {
        if (! Schema::hasColumn('ai_query_logs', 'openai_calls')) {
            $this->error('Profiling columns are missing. Run: php artisan migrate');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));

        $rows = AiQueryLog::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('openai_calls')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['duration_ms', 'openai_calls', 'openai_ms', 'db_ms', 'engine', 'fallbacks', 'stage_timings', 'question_hash']);

        if ($rows->isEmpty()) {
            $this->warn("No profiled rows in the last {$days} day(s). Is AI_PROFILING=true and has anyone used the chat?");

            return self::SUCCESS;
        }

        $count = $rows->count();
        $durations = $rows->pluck('duration_ms')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->all();
        $calls = $rows->pluck('openai_calls')->map(fn ($v) => (int) $v)->all();

        $avgOpenAiMs = (int) round($rows->avg('openai_ms'));
        $avgDbMs = (int) round($rows->avg('db_ms'));
        $avgPhpMs = (int) round($rows->avg(fn ($r) => (int) ($r->stage_timings['php_ms'] ?? max(0, (int) $r->duration_ms - (int) $r->openai_ms - (int) $r->db_ms))));

        // Engine distribution.
        $engines = $rows->groupBy(fn ($r) => $r->engine ?: 'unknown')
            ->map->count()
            ->sortDesc();

        // Fallback rate.
        $withFallback = $rows->filter(fn ($r) => (int) $r->fallbacks > 0)->count();

        // Repeat-question rate (duplicate normalized questions in the window).
        $hashes = $rows->pluck('question_hash')->filter();
        $uniqueHashes = $hashes->unique()->count();
        $repeatRate = $hashes->count() > 0 ? round((1 - $uniqueHashes / $hashes->count()) * 100, 1) : 0.0;

        // Per-stage median wall time.
        $stageMs = [];
        foreach ($rows as $r) {
            foreach (($r->stage_timings['stages'] ?? []) as $name => $data) {
                $stageMs[$name][] = (int) ($data['ms'] ?? 0);
            }
        }
        $stageMedians = collect($stageMs)
            ->map(fn ($vals) => ['p50' => $this->percentile($vals, 50), 'p95' => $this->percentile($vals, 95), 'n' => count($vals)])
            ->sortByDesc('p50');

        $report = [
            'window_days' => $days,
            'sampled_requests' => $count,
            'latency_ms' => [
                'p50' => $this->percentile($durations, 50),
                'p95' => $this->percentile($durations, 95),
                'max' => $durations ? max($durations) : 0,
            ],
            'openai_calls' => [
                'avg' => round(array_sum($calls) / max(1, count($calls)), 2),
                'p95' => $this->percentile($calls, 95),
                'max' => $calls ? max($calls) : 0,
            ],
            'latency_split_ms' => [
                'openai' => $avgOpenAiMs,
                'db' => $avgDbMs,
                'php' => $avgPhpMs,
            ],
            'engine_distribution' => $engines->all(),
            'fallback_rate_pct' => round($withFallback / $count * 100, 1),
            'repeat_question_rate_pct' => $repeatRate,
            'stage_medians_ms' => $stageMedians->all(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->renderHuman($report);

        return self::SUCCESS;
    }

    private function renderHuman(array $r): void
    {
        $this->info("AI Chat profiling — last {$r['window_days']} day(s), {$r['sampled_requests']} request(s)");
        $this->newLine();

        $this->line('<fg=cyan>Latency (total)</>');
        $this->line("  p50 {$r['latency_ms']['p50']} ms   p95 {$r['latency_ms']['p95']} ms   max {$r['latency_ms']['max']} ms");
        $this->newLine();

        $this->line('<fg=cyan>OpenAI round-trips per question</>  <fg=gray>(the headline cost metric)</>');
        $this->line("  avg {$r['openai_calls']['avg']}   p95 {$r['openai_calls']['p95']}   max {$r['openai_calls']['max']}");
        $this->newLine();

        $split = $r['latency_split_ms'];
        $totalSplit = max(1, $split['openai'] + $split['db'] + $split['php']);
        $this->line('<fg=cyan>Where the time goes (avg ms)</>');
        $this->line(sprintf('  OpenAI %d ms (%d%%)   DB %d ms (%d%%)   PHP %d ms (%d%%)',
            $split['openai'], (int) round($split['openai'] / $totalSplit * 100),
            $split['db'], (int) round($split['db'] / $totalSplit * 100),
            $split['php'], (int) round($split['php'] / $totalSplit * 100),
        ));
        $this->newLine();

        $this->line('<fg=cyan>Engine that answered</>');
        foreach ($r['engine_distribution'] as $engine => $n) {
            $pct = (int) round($n / $r['sampled_requests'] * 100);
            $this->line("  {$engine}: {$n} ({$pct}%)");
        }
        $this->newLine();

        $this->line('<fg=cyan>Fallback rate</>: ' . $r['fallback_rate_pct'] . '%   <fg=gray>(requests that fell back at least once)</>');
        $this->line('<fg=cyan>Repeat-question rate</>: ' . $r['repeat_question_rate_pct'] . '%   <fg=gray>(caching potential)</>');
        $this->newLine();

        $this->line('<fg=cyan>Per-stage wall time (ms)</>');
        foreach ($r['stage_medians_ms'] as $stage => $m) {
            $this->line(sprintf('  %-18s p50 %5d   p95 %5d   (n=%d)', $stage, $m['p50'], $m['p95'], $m['n']));
        }
    }

    /**
     * Nearest-rank percentile of an integer list.
     *
     * @param  array<int,int>  $values
     */
    private function percentile(array $values, int $percentile): int
    {
        if ($values === []) {
            return 0;
        }

        sort($values);
        $rank = (int) ceil($percentile / 100 * count($values));
        $index = max(0, min(count($values) - 1, $rank - 1));

        return (int) $values[$index];
    }
}
