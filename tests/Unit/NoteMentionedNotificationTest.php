<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\NoteMentionedNotification;
use PHPUnit\Framework\TestCase;

class NoteMentionedNotificationTest extends TestCase
{
    public function test_mention_is_emailed_only_to_users_who_opted_in(): void
    {
        $notification = new NoteMentionedNotification(null, 'note', null);

        $optedIn = new User(['email' => 'a@example.com', 'email_preference' => 1]);
        $optedOut = new User(['email' => 'b@example.com', 'email_preference' => 0]);
        $noEmail = new User(['email' => null, 'email_preference' => 1]);

        $this->assertSame(['database', 'mail'], $notification->via($optedIn));
        $this->assertSame(['database'], $notification->via($optedOut));
        $this->assertSame(['database'], $notification->via($noEmail));
    }
}
