<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

/**
 * Runs the AI chat evaluation cases (config/ai_eval.php) through the real
 * pipeline and reports pass/fail per case. Catches routing/quality regressions
 * automatically instead of relying on users to notice empty results.
 *
 *   php artisan ai:eval                 # run as the first admin user
 *   php artisan ai:eval --user=12       # run as a specific user id
 *   php artisan ai:eval --keep          # do not delete the chats it creates
 *
 * NOTE: this makes live OpenAI calls and reads your real database.
 */
class AiEvalCommand extends Command
{
    protected $signature = 'ai:eval {--user= : Run as this user id (defaults to first admin)} {--keep : Keep the chats created during evaluation} {--show : Print the first few answer rows for each case}';

    protected $description = 'Run the AI chat evaluation suite and report routing/quality regressions';

    public function handle(AiChatService $chat): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('No user found. Pass --user=<id> or create an admin user.');

            return self::FAILURE;
        }

        $cases = (array) config('ai_eval.cases', []);

        if ($cases === []) {
            $this->warn('No eval cases configured in config/ai_eval.php.');

            return self::SUCCESS;
        }

        $this->info("Running " . count($cases) . " eval case(s) as user #{$user->id} ({$user->name})\n");

        $passed = 0;
        $failed = 0;

        foreach ($cases as $case) {
            $question = $this->caseLabel($case);

            if ($question === '') {
                continue;
            }

            [$ok, $detail, $cleanup, $rows] = $this->runCase($chat, $user, $case);

            if ($ok) {
                $passed++;
                $this->line('<fg=green>PASS</> ' . $question . '  <fg=gray>(' . $detail . ')</>');
            } else {
                $failed++;
                $this->line('<fg=red>FAIL</> ' . $question . '  <fg=yellow>(' . $detail . ')</>');
            }

            if ($this->option('show')) {
                foreach (array_slice($rows, 0, 6) as $row) {
                    $this->line('       <fg=gray>' . Str::limit(json_encode($row), 150) . '</>');
                }
            }

            if ($cleanup && ! $this->option('keep')) {
                $cleanup();
            }
        }

        $this->newLine();
        $this->line("Passed: <fg=green>{$passed}</>   Failed: " . ($failed > 0 ? "<fg=red>{$failed}</>" : '0'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * A short display label for a case (single or multi-turn).
     */
    private function caseLabel(array $case): string
    {
        if (! empty($case['q'])) {
            return (string) $case['q'];
        }

        if (! empty($case['name'])) {
            return (string) $case['name'];
        }

        $steps = (array) ($case['steps'] ?? []);
        $first = $steps[0]['q'] ?? '';
        $label = (string) $first;

        return $label === '' ? '' : '[chain] ' . $label . ' → …';
    }

    /**
     * Run a case. A case is either a single question (`q` + expectations) or a
     * multi-turn conversation (`steps` => [ {q, expectations}, ... ]) executed in
     * ONE chat so follow-ups ("show details of these", "filter by California
     * too") carry context — exactly how a real user chats.
     *
     * @return array{0: bool, 1: string, 2: ?callable, 3: array}
     */
    private function runCase(AiChatService $chat, User $user, array $case): array
    {
        $steps = isset($case['steps']) && is_array($case['steps']) ? $case['steps'] : [$case];

        $chatModel = null;
        $cleanup   = null;
        $lastRows  = [];
        $summaries = [];

        foreach (array_values($steps) as $index => $step) {
            $question = (string) ($step['q'] ?? '');

            if ($question === '') {
                continue;
            }

            try {
                $chatModel = $chat->send($user, $question, $chatModel?->id);
            } catch (Throwable $e) {
                $cleanup ??= $chatModel ? fn () => $chat->delete($chatModel) : null;

                return [false, $this->stepLabel($steps, $index, $question) . 'send() threw: ' . Str::limit($e->getMessage(), 80), $cleanup, $lastRows];
            }

            $cleanup = fn () => $chat->delete($chatModel);

            $message = $chatModel->messages->last();
            $meta    = is_array($message->metadata) ? $message->metadata : [];
            $content = (string) ($message->content ?? '');

            $intent   = (string) Arr::get($meta, 'query_plan.intent', '');
            $rows     = (array) Arr::get($meta, 'answer.rows', []);
            $lastRows = $rows;
            $rowCount = count($rows);
            $countVal = $this->countValue($meta);

            $summary = "intent={$intent} rows={$rowCount}" . ($countVal !== null ? " count={$countVal}" : '');
            $summaries[] = $summary;

            [$ok, $detail] = $this->evaluateStep($step, $intent, $rowCount, $countVal, $content, $summary);

            if (! $ok) {
                return [false, $this->stepLabel($steps, $index, $question) . $detail, $cleanup, $rows];
            }
        }

        return [true, implode(' | ', $summaries), $cleanup, $lastRows];
    }

    /**
     * Evaluate one step's expectations.
     *
     * @return array{0: bool, 1: string}
     */
    private function evaluateStep(array $step, string $intent, int $rowCount, ?int $countVal, string $content, string $summary): array
    {
        if (isset($step['intent']) && ! str_contains($intent, (string) $step['intent'])) {
            return [false, "expected intent~'{$step['intent']}' but got '{$intent}'"];
        }

        if (isset($step['count_min'])) {
            if ($countVal === null) {
                return [false, "expected a count >= {$step['count_min']} but no count value; {$summary}"];
            }
            if ($countVal < (int) $step['count_min']) {
                return [false, "count {$countVal} < expected {$step['count_min']}"];
            }
        }

        if (isset($step['min_rows']) && $rowCount < (int) $step['min_rows']) {
            return [false, "rows {$rowCount} < expected {$step['min_rows']}; {$summary}"];
        }

        if (isset($step['max_rows']) && $rowCount > (int) $step['max_rows']) {
            return [false, "rows {$rowCount} > expected max {$step['max_rows']} (filter likely lost); {$summary}"];
        }

        if (isset($step['contains'])) {
            foreach ((array) $step['contains'] as $needle) {
                if (stripos($content, (string) $needle) === false) {
                    return [false, "answer missing expected text '{$needle}'; got: " . Str::limit($content, 90)];
                }
            }
        }

        if (isset($step['not_contains'])) {
            foreach ((array) $step['not_contains'] as $needle) {
                if (stripos($content, (string) $needle) !== false) {
                    return [false, "answer wrongly contains '{$needle}'; got: " . Str::limit($content, 90)];
                }
            }
        }

        // Default expectation when none specified: the answer must carry data.
        $hasExplicit = isset($step['intent']) || isset($step['count_min'])
            || isset($step['min_rows']) || isset($step['contains']) || isset($step['not_contains']);

        if (! $hasExplicit) {
            $hasData = $rowCount > 0 || ($countVal !== null && $countVal > 0);
            if (! $hasData) {
                return [false, "no data returned; {$summary}"];
            }
        }

        return [true, $summary];
    }

    /**
     * "step 2/3 'question': " prefix for multi-turn cases, empty for single.
     *
     * @param array<int,array> $steps
     */
    private function stepLabel(array $steps, int $index, string $question): string
    {
        if (count($steps) <= 1) {
            return '';
        }

        return 'step ' . ($index + 1) . '/' . count($steps) . " '" . Str::limit($question, 40) . "': ";
    }

    /**
     * Extract a numeric count value from a count-type answer, or null.
     */
    private function countValue(array $meta): ?int
    {
        $cards = (array) Arr::get($meta, 'answer.cards', []);
        foreach ($cards as $card) {
            if (isset($card['value']) && is_numeric($card['value'])) {
                return (int) $card['value'];
            }
        }

        $rows = (array) Arr::get($meta, 'answer.rows', []);
        $first = is_array($rows[0] ?? null) ? $rows[0] : [];
        $value = $first['value'] ?? $first['aggregate'] ?? $first['count'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    private function resolveUser(): ?User
    {
        if ($id = $this->option('user')) {
            return User::find($id);
        }

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin']))->first();
    }
}
