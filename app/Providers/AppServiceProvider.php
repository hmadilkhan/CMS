<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        if (! app()->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        $this->blockAiWriteOperations();
    }

    private function blockAiWriteOperations(): void
    {
        if (! config('ai.enable_write_block', true)) {
            return;
        }

        DB::listen(function ($query) {
            if (! $this->isAiContext()) {
                return;
            }

            $sql = strtolower(trim($query->sql));
            $writeOperations = [
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
            ];

            foreach ($writeOperations as $operation) {
                if (str_starts_with($sql, $operation)) {
                    Log::critical('AI write operation blocked.', [
                        'operation' => $operation,
                        'sql' => $query->sql,
                        'user_id' => auth()->id(),
                        'ip' => request()?->ip(),
                    ]);

                    throw new \RuntimeException('Write operations are not allowed in AI chat context.');
                }
            }
        });
    }

    private function isAiContext(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15) as $frame) {
            $class = $frame['class'] ?? '';

            if ($class === \App\Services\AiQueryExecutorService::class) {
                return true;
            }
        }

        return false;
    }
}
