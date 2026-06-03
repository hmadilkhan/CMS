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
            $question = (string) ($case['q'] ?? '');

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
     * @return array{0: bool, 1: string, 2: ?callable, 3: array}
     */
    private function runCase(AiChatService $chat, User $user, array $case): array
    {
        $question = (string) $case['q'];

        try {
            $result = $chat->send($user, $question);
        } catch (Throwable $e) {
            return [false, 'send() threw: ' . Str::limit($e->getMessage(), 80), null, []];
        }

        $message = $result->messages->last();
        $meta    = is_array($message->metadata) ? $message->metadata : [];
        $cleanup = fn () => $chat->delete($result);

        $intent   = (string) Arr::get($meta, 'query_plan.intent', '');
        $rows     = (array) Arr::get($meta, 'answer.rows', []);
        $rowCount = count($rows);
        $countVal = $this->countValue($meta);

        $summary = "intent={$intent} rows={$rowCount}" . ($countVal !== null ? " count={$countVal}" : '');

        // --- Evaluate expectations ---------------------------------------------
        if (isset($case['intent']) && ! str_contains($intent, (string) $case['intent'])) {
            return [false, "expected intent~'{$case['intent']}' but got '{$intent}'", $cleanup, $rows];
        }

        if (isset($case['count_min'])) {
            if ($countVal === null) {
                return [false, "expected a count >= {$case['count_min']} but no count value; {$summary}", $cleanup, $rows];
            }
            if ($countVal < (int) $case['count_min']) {
                return [false, "count {$countVal} < expected {$case['count_min']}", $cleanup, $rows];
            }
        }

        if (isset($case['min_rows']) && $rowCount < (int) $case['min_rows']) {
            return [false, "rows {$rowCount} < expected {$case['min_rows']}; {$summary}", $cleanup, $rows];
        }

        // Default expectation when none specified: the answer must carry data.
        $hasExplicitExpectation = isset($case['intent']) || isset($case['count_min']) || isset($case['min_rows']);
        if (! $hasExplicitExpectation) {
            $hasData = $rowCount > 0 || ($countVal !== null && $countVal > 0);
            if (! $hasData) {
                return [false, "no data returned; {$summary}", $cleanup, $rows];
            }
        }

        return [true, $summary, $cleanup, $rows];
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
