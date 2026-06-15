<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AiDailyLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $limit = (int) config('ai.security.max_daily_requests_per_user', 100);

        if ($limit <= 0) {
            return $next($request);
        }

        $key = 'ai_daily_usage:' . $user->id . ':' . now()->format('Y-m-d');

        // Atomically check-and-increment under a per-user lock so two concurrent
        // requests (e.g. multiple tabs) cannot both read the same count and slip
        // past the cap (a read-then-write race). The lock is held only for the
        // fast counter update — released before $next() runs, so it never serialises
        // the slow downstream OpenAI work.
        $lock = Cache::lock('ai_daily_lock:' . $user->id, 5);
        $acquired = false;

        try {
            $acquired = $lock->block(3);

            $current = (int) Cache::get($key, 0);

            if ($current >= $limit) {
                return response()->json([
                    'message' => 'Daily AI request limit reached. Please try again tomorrow.',
                ], 429);
            }

            Cache::put($key, $current + 1, now()->endOfDay());
        } catch (LockTimeoutException) {
            // Could not acquire the lock in time — fail open and let the request
            // through. The cap is a soft abuse guard, not a hard security control.
        } finally {
            if ($acquired) {
                $lock->release();
            }
        }

        return $next($request);
    }
}
