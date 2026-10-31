<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Keeps queued mail inside the SMTP account's sending limit.
 *
 * Every CRM email leaves through the same mailbox, and the provider answers
 * "451 Ratelimit exceeded" once it has had enough. Without this the job simply
 * failed and the email was lost. Now:
 *  - sends are paced to config('mail.throttle.per_minute');
 *  - a rate-limit rejection starts a shared cooldown, and this job and every
 *    other mail job go back on the queue until it ends, instead of failing.
 *
 * Any other exception is rethrown, so real errors still fail the job.
 */
class ThrottleMail
{
    private const PACE_KEY = 'mail-throttle:pace';

    private const COOLDOWN_KEY = 'mail-throttle:cooldown-until';

    public function handle(object $job, Closure $next): void
    {
        $cooldownLeft = (int) Cache::get(self::COOLDOWN_KEY, 0) - now()->getTimestamp();
        if ($cooldownLeft > 0) {
            $job->release($cooldownLeft);

            return;
        }

        $perMinute = (int) config('mail.throttle.per_minute', 5);
        if ($perMinute > 0) {
            if (RateLimiter::tooManyAttempts(self::PACE_KEY, $perMinute)) {
                $job->release(max(RateLimiter::availableIn(self::PACE_KEY), 1));

                return;
            }
            RateLimiter::hit(self::PACE_KEY, 60);
        }

        try {
            $next($job);
        } catch (Throwable $e) {
            if (! self::isRateLimited($e)) {
                throw $e;
            }

            $cooldown = max((int) config('mail.throttle.cooldown_seconds', 600), 60);
            Cache::put(self::COOLDOWN_KEY, now()->getTimestamp() + $cooldown, $cooldown);
            Log::warning('Mail rate limit hit; pausing queued mail', [
                'job' => get_class($job),
                'cooldown_seconds' => $cooldown,
                'error' => $e->getMessage(),
            ]);

            $job->release($cooldown);
        }
    }

    /**
     * True when the SMTP server refused the message for sending too much
     * (Hostinger: "451 4.7.1 Ratelimit ... exceeded"), as opposed to a bad
     * address or a broken template.
     */
    public static function isRateLimited(Throwable $e): bool
    {
        for ($current = $e; $current; $current = $current->getPrevious()) {
            $message = $current->getMessage();

            if (preg_match('/rate\s*limit|too many (messages|emails|connections)/i', $message)) {
                return true;
            }

            if (in_array((int) $current->getCode(), [421, 451, 452], true)) {
                return true;
            }
        }

        return false;
    }
}
