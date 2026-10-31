<?php

namespace Tests\Feature;

use App\Jobs\Middleware\ThrottleMail;
use App\Jobs\SendHtmlEmailJob;
use App\Notifications\NoteMentionedNotification;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

class ThrottleMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'mail.throttle.per_minute' => 2, 'mail.throttle.cooldown_seconds' => 600]);
        Cache::flush();
    }

    private function job(): object
    {
        return new class
        {
            public ?int $releasedFor = null;

            public function release($delay = 0): void
            {
                $this->releasedFor = $delay;
            }
        };
    }

    private function hostingerRateLimit(): UnexpectedResponseException
    {
        return new UnexpectedResponseException(
            'Expected response code "250" but got code "451", with message "451 4.7.1 Ratelimit "hostinger_out_ratelimit" exceeded".',
            451
        );
    }

    public function test_mail_is_sent_while_under_the_pace(): void
    {
        $job = $this->job();
        $sent = false;

        (new ThrottleMail)->handle($job, function () use (&$sent) {
            $sent = true;
        });

        $this->assertTrue($sent);
        $this->assertNull($job->releasedFor);
    }

    public function test_mail_over_the_pace_waits_instead_of_sending(): void
    {
        $middleware = new ThrottleMail;
        $middleware->handle($this->job(), fn () => null);
        $middleware->handle($this->job(), fn () => null);

        $third = $this->job();
        $sent = false;
        $middleware->handle($third, function () use (&$sent) {
            $sent = true;
        });

        $this->assertFalse($sent);
        $this->assertGreaterThan(0, $third->releasedFor);
    }

    public function test_a_rate_limit_rejection_puts_the_job_back_and_pauses_all_mail(): void
    {
        $middleware = new ThrottleMail;

        $rejected = $this->job();
        $middleware->handle($rejected, function () {
            throw $this->hostingerRateLimit();
        });
        $this->assertSame(600, $rejected->releasedFor);

        // The next mail job does not even try while the cooldown runs.
        $next = $this->job();
        $sent = false;
        $middleware->handle($next, function () use (&$sent) {
            $sent = true;
        });

        $this->assertFalse($sent);
        $this->assertGreaterThan(590, $next->releasedFor);
    }

    public function test_other_errors_still_fail_the_job(): void
    {
        $this->expectException(RuntimeException::class);

        (new ThrottleMail)->handle($this->job(), function () {
            throw new RuntimeException('Template missing');
        });
    }

    public function test_jobs_wait_out_the_limit_instead_of_counting_tries(): void
    {
        $job = new SendHtmlEmailJob('a@example.com', 'Subject', 'Body');

        $this->assertInstanceOf(ThrottleMail::class, $job->middleware()[0]);
        $this->assertSame(3, $job->maxExceptions);
        $this->assertTrue($job->retryUntil()->greaterThan(now()->addHours(23)));
    }

    public function test_only_the_mail_channel_of_a_notification_is_throttled(): void
    {
        $notification = new NoteMentionedNotification(null, 'note', null);

        $this->assertInstanceOf(ThrottleMail::class, $notification->middleware(null, 'mail')[0]);
        $this->assertSame([], $notification->middleware(null, 'database'));
    }
}
