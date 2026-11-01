<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\EmailReceivedNotification;
use App\Notifications\NoteMentionedNotification;
use App\Notifications\ProjectAssignedEmailNotification;
use App\Notifications\ProjectDateUpdatedNotification;
use App\Notifications\ServiceTicketCommentAdded;
use App\Notifications\ServiceTicketCreated;
use App\Notifications\ServiceTicketResolved;
use App\Notifications\SiteSurveyScheduled;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserEmailPreferenceTest extends TestCase
{
    public function test_a_user_wants_an_email_unless_the_master_or_that_template_is_off(): void
    {
        $all = new User(['email' => 'a@example.com', 'email_preference' => 1]);
        $someOff = new User(['email' => 'b@example.com', 'email_preference' => 1, 'email_opt_outs' => ['note_mentioned']]);
        $masterOff = new User(['email' => 'c@example.com', 'email_preference' => 0]);
        $noAddress = new User(['email' => null, 'email_preference' => 1]);

        $this->assertTrue($all->wantsEmail('note_mentioned'));
        $this->assertFalse($someOff->wantsEmail('note_mentioned'));
        $this->assertTrue($someOff->wantsEmail('project_assigned'));
        $this->assertFalse($masterOff->wantsEmail('project_assigned'));
        $this->assertFalse($noAddress->wantsEmail('project_assigned'));
    }

    /** @return array<string, array{class-string, string, array}> */
    public static function notifications(): array
    {
        return [
            'project assigned' => [ProjectAssignedEmailNotification::class, 'project_assigned', []],
            'project date updated' => [ProjectDateUpdatedNotification::class, 'project_date_updated', []],
            'note mentioned' => [NoteMentionedNotification::class, 'note_mentioned', ['database']],
            'email received' => [EmailReceivedNotification::class, 'email_received', ['database']],
            'ticket created' => [ServiceTicketCreated::class, 'service_ticket_created', ['database']],
            'ticket resolved' => [ServiceTicketResolved::class, 'service_ticket_resolved', ['database']],
            'ticket comment' => [ServiceTicketCommentAdded::class, 'service_ticket_comment', ['database']],
            'site survey' => [SiteSurveyScheduled::class, 'site_survey_scheduled', ['database']],
        ];
    }

    #[DataProvider('notifications')]
    public function test_each_email_follows_its_own_switch_and_the_bell_entry_stays(string $class, string $key, array $withoutMail): void
    {
        $notification = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        $on = new User(['email' => 'a@example.com', 'email_preference' => 1]);
        $off = new User(['email' => 'a@example.com', 'email_preference' => 1, 'email_opt_outs' => [$key]]);
        $other = new User(['email' => 'a@example.com', 'email_preference' => 1, 'email_opt_outs' => ['some_other_template']]);
        $masterOff = new User(['email' => 'a@example.com', 'email_preference' => 0]);

        $this->assertContains('mail', $notification->via($on));
        $this->assertContains('mail', $notification->via($other));
        $this->assertSame($withoutMail, $notification->via($off));
        $this->assertSame($withoutMail, $notification->via($masterOff));
    }
}
