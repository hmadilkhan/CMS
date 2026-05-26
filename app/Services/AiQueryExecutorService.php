<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiQueryExecutorService
{
    public function execute(array $sqlPreview): array
    {
        try {
            $connectionName = config('database.ai_readonly_connection');

            /*
             * Optional read-only setup:
             * 1. Add AI_READONLY_DB_CONNECTION=ai_readonly to .env.
             * 2. Add a database.connections.ai_readonly entry in config/database.php
             *    that points to a read-only database user.
             * 3. Grant that DB user SELECT only.
             */
            $connection = $connectionName && config("database.connections.{$connectionName}")
                ? DB::connection($connectionName)
                : DB::connection();

            $rows = $connection->select($sqlPreview['sql'], $sqlPreview['bindings'] ?? []);

            return [
                'success' => true,
                'rows' => array_map(fn ($row) => (array) $row, $rows),
                'row_count' => count($rows),
                'connection' => $connectionName && config("database.connections.{$connectionName}") ? $connectionName : config('database.default'),
                'error_message' => null,
            ];
        } catch (Throwable $exception) {
            Log::warning('AI query execution failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'rows' => [],
                'row_count' => 0,
                'connection' => config('database.ai_readonly_connection') ?: config('database.default'),
                'error_message' => 'I could not safely run this CRM query. Please try again or contact an administrator.',
            ];
        }
    }
}
