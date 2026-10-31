<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\ThrottleMail;
use DateTimeInterface;

/**
 * For queued jobs and notifications that send email: runs them through
 * ThrottleMail, and lets them wait out the SMTP rate limit instead of failing.
 *
 * A job put back by the throttle still counts an attempt, so a fixed number
 * of tries would fail it after a few waits. retryUntil() replaces that with a
 * time window, and $maxExceptions keeps real errors failing after three.
 */
trait ThrottlesMail
{
    public $maxExceptions = 3;

    /**
     * Jobs are called with no arguments; a queued notification is called once
     * per channel, and only its mail channel needs throttling.
     */
    public function middleware($notifiable = null, ?string $channel = null): array
    {
        return $channel === null || $channel === 'mail' ? [new ThrottleMail] : [];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours((int) config('mail.throttle.give_up_after_hours', 24));
    }
}
