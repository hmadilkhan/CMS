<?php

namespace App\Services;

/**
 * Request-scoped latency / token profiler for the AI chat pipeline (Phase 0).
 *
 * PURE OBSERVABILITY: it collects per-stage wall time, OpenAI round-trips and
 * tokens, and DB time for a single question. It NEVER influences routing,
 * planning, scoping, or results. Every method is a cheap no-op when profiling
 * is disabled (config ai.profiling.enabled), so leaving it wired in costs about
 * a boolean check per call.
 *
 * Bound as a scoped singleton (one shared instance per HTTP request / console
 * command) so OpenAiService, AiQueryExecutorService and the orchestrator all
 * record into the same collector. reset() is called at the start of each
 * question so a multi-intent turn produces one clean profile per sub-question.
 *
 * Stage timing uses simple sequential markers: stage('planner') closes the
 * previously open stage (banking its elapsed time) and opens the new one — no
 * closure wrapping is required at call sites.
 */
class AiProfiler
{
    private bool $enabled;

    /** @var array<string,array{ms:float,calls:int,tokens:int}> */
    private array $stages = [];

    private ?string $currentStage = null;
    private float $currentStageStart = 0.0;

    private int $openaiCalls = 0;
    private float $openaiMs = 0.0;
    private float $dbMs = 0.0;
    private int $fallbacks = 0;

    /** @var array<int,string> */
    private array $fallbackReasons = [];

    private ?string $engine = null;

    public function __construct()
    {
        $this->enabled = (bool) config('ai.profiling.enabled', false);
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Toggle profiling at runtime. Used by `ai:eval --profile` to switch on the
     * already-resolved shared instance for a benchmark run.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Clear all collected data. Called at the start of each question so a
     * multi-intent turn records one profile per sub-question instead of mixing.
     */
    public function reset(): void
    {
        $this->stages = [];
        $this->currentStage = null;
        $this->currentStageStart = 0.0;
        $this->openaiCalls = 0;
        $this->openaiMs = 0.0;
        $this->dbMs = 0.0;
        $this->fallbacks = 0;
        $this->fallbackReasons = [];
        $this->engine = null;
    }

    /**
     * Mark the start of a named pipeline stage. Closes the previously open stage
     * (accumulating its wall time) so stages are timed by sequential markers.
     */
    public function stage(string $label): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->closeStage();
        $this->currentStage = $label;
        $this->currentStageStart = microtime(true);

        if (! isset($this->stages[$label])) {
            $this->stages[$label] = ['ms' => 0.0, 'calls' => 0, 'tokens' => 0];
        }
    }

    /**
     * Record one logical OpenAI step (which may have been several HTTP attempts
     * internally). Time/tokens are attributed to the currently open stage.
     *
     * @param  array<string,mixed>  $usage  OpenAI usage block
     */
    public function recordOpenAi(int $ms, array $usage = [], int $attempts = 1): void
    {
        if (! $this->enabled) {
            return;
        }

        $calls = max(1, $attempts);
        $tokens = (int) ($usage['total_tokens'] ?? 0);

        $this->openaiCalls += $calls;
        $this->openaiMs += $ms;

        if ($this->currentStage !== null) {
            $this->stages[$this->currentStage]['calls'] += $calls;
            $this->stages[$this->currentStage]['tokens'] += $tokens;
        }
    }

    /**
     * Record pure database execution time (the SELECT itself), kept separate
     * from OpenAI and PHP time.
     */
    public function recordDb(int $ms): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->dbMs += $ms;
    }

    public function incrementFallback(string $reason): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->fallbacks++;
        $this->fallbackReasons[] = $reason;
    }

    public function setEngine(string $engine): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->engine = $engine;
    }

    /**
     * Snapshot the profile as column values for ai_query_logs. Returns [] when
     * profiling is disabled, so the caller writes nothing. $durationMs is the
     * total request time already measured by the caller; php_ms is derived from
     * it (total minus OpenAI minus DB).
     *
     * @return array<string,mixed>
     */
    public function toColumns(int $durationMs, ?string $engine = null): array
    {
        if (! $this->enabled) {
            return [];
        }

        $this->closeStage();

        $openaiMs = (int) round($this->openaiMs);
        $dbMs = (int) round($this->dbMs);
        $phpMs = max(0, $durationMs - $openaiMs - $dbMs);

        $stages = [];
        foreach ($this->stages as $name => $data) {
            $stages[$name] = [
                'ms' => (int) round($data['ms']),
                'calls' => $data['calls'],
                'tokens' => $data['tokens'],
            ];
        }

        return [
            'engine' => $engine ?? $this->engine,
            'openai_calls' => $this->openaiCalls,
            'openai_ms' => $openaiMs,
            'db_ms' => $dbMs,
            'fallbacks' => $this->fallbacks,
            'stage_timings' => [
                'stages' => $stages,
                'php_ms' => $phpMs,
                'fallback_reasons' => $this->fallbackReasons,
            ],
        ];
    }

    private function closeStage(): void
    {
        if ($this->currentStage === null) {
            return;
        }

        $elapsed = (microtime(true) - $this->currentStageStart) * 1000;
        $this->stages[$this->currentStage]['ms'] += $elapsed;
        $this->currentStage = null;
    }
}
