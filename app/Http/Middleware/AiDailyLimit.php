<?php

namespace App\Http\Middleware;

use Closure;
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
        $current = (int) Cache::get($key, 0);

        if ($current >= $limit) {
            return response()->json([
                'message' => 'Daily AI request limit reached. Please try again tomorrow.',
            ], 429);
        }

        Cache::put($key, $current + 1, now()->endOfDay());

        return $next($request);
    }
}
