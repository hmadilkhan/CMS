<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiQueryExecutorService
{
    public function execute(array $sqlPreview, ?int $userId = null): array
    {
        $cacheKey = $this->cacheKey($sqlPreview, $userId);
        $ttl = (int) config('ai.security.query_cache_ttl', 300);

        if ($ttl > 0 && $cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['cached' => true]);
        }

        $connectionName = config('database.ai_readonly_connection');
        $resolvedConnection = $connectionName && config("database.connections.{$connectionName}") ? $connectionName : config('database.default');
        $timeoutApplied = false;

        try {
            $connection = $connectionName && config("database.connections.{$connectionName}")
                ? DB::connection($connectionName)
                : DB::connection();

            $timeoutMs = (int) config('ai.security.query_timeout_ms', 5000);
            if ($timeoutMs > 0 && $connection->getDriverName() === 'mysql') {
                try {
                    $connection->statement('SET SESSION MAX_EXECUTION_TIME=' . $timeoutMs);
                    $timeoutApplied = true;
                } catch (Throwable $timeoutException) {
                    Log::info('AI query timeout setting is not supported by this database server.', [
                        'message' => $timeoutException->getMessage(),
                    ]);
                }
            }

            $rows = $connection->select($sqlPreview['sql'], $sqlPreview['bindings'] ?? []);

            $result = [
                'success' => true,
                'rows' => array_map(fn ($row) => (array) $row, $rows),
                'row_count' => count($rows),
                'connection' => $resolvedConnection,
                'error_message' => null,
                'cached' => false,
            ];

            if ($ttl > 0 && count($rows) > 0) {
                Cache::put($cacheKey, $result, $ttl);
            }

            return $result;
        } catch (Throwable $exception) {
            Log::warning('AI query execution failed.', [
                'message' => $exception->getMessage(),
            ]);

            $message = str_contains(strtolower($exception->getMessage()), 'timeout') || str_contains(strtolower($exception->getMessage()), 'exceeded')
                ? 'Query took too long. Please ask a more specific question.'
                : 'I could not safely run this CRM query. Please try again or contact an administrator.';

            return [
                'success' => false,
                'rows' => [],
                'row_count' => 0,
                'connection' => $resolvedConnection,
                'error_message' => $message,
                'cached' => false,
            ];
        } finally {
            if ($timeoutApplied) {
                try {
                    ($connectionName && config("database.connections.{$connectionName}") ? DB::connection($connectionName) : DB::connection())
                        ->statement('SET SESSION MAX_EXECUTION_TIME=0');
                } catch (Throwable) {
                    // Timeout reset failures should not leak to the user-facing layer.
                }
            }
        }
    }

    private function cacheKey(array $sqlPreview, ?int $userId): string
    {
        return 'ai_query:' . md5(json_encode([
            'user_id' => $userId,
            'sql' => $sqlPreview['sql'] ?? '',
            'bindings' => $sqlPreview['bindings'] ?? [],
        ]));
    }
}
