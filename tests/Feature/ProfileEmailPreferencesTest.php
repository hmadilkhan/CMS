<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEmailPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function save(User $user, array $data)
    {
        return $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            ...$data,
        ]);
    }

    public function test_the_nine_user_emails_are_offered_and_the_fixed_address_ones_are_not(): void
    {
        $keys = NotificationTemplateService::userSwitchableKeys();

        $this->assertCount(9, $keys);
        $this->assertNotContains('acceptance_review_status', $keys);
        $this->assertNotContains('finance_milestone_triggered', $keys);
    }

    public function test_the_profile_lists_every_switch_and_all_are_on_for_a_new_user(): void
    {
        $user = User::factory()->create(['email_preference' => 1]);

        $page = $this->actingAs($user)->get('/profile')->assertOk();

        foreach (NotificationTemplateService::userSwitchableKeys() as $key) {
            $page->assertSee('name="email_templates['.$key.']"', false);
        }

        $this->assertSame(9, preg_match_all('/name="email_templates\[[a-z_]+\]"\s+value="1"\s+checked\s*>/', $page->getContent()));
    }

    public function test_switching_emails_off_is_saved_per_user(): void
    {
        $user = User::factory()->create(['email_preference' => 1]);
        $other = User::factory()->create(['email_preference' => 1]);

        $this->save($user, [
            'email_preference' => 1,
            'email_templates_submitted' => 1,
            'email_templates' => ['project_assigned' => 1, 'service_ticket_created' => 1],
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->wantsEmail('project_assigned'));
        $this->assertTrue($user->wantsEmail('service_ticket_created'));
        $this->assertFalse($user->wantsEmail('note_mentioned'));
        $this->assertFalse($user->wantsEmail('site_survey_scheduled'));
        $this->assertCount(7, $user->email_opt_outs);

        $this->assertTrue($other->fresh()->wantsEmail('note_mentioned'));
    }

    public function test_a_switched_off_email_can_be_switched_back_on(): void
    {
        $user = User::factory()->create(['email_preference' => 1, 'email_opt_outs' => ['note_mentioned']]);

        $this->save($user, [
            'email_preference' => 1,
            'email_templates_submitted' => 1,
            'email_templates' => array_fill_keys(NotificationTemplateService::userSwitchableKeys(), 1),
        ]);

        $this->assertTrue($user->fresh()->wantsEmail('note_mentioned'));
        $this->assertSame([], $user->fresh()->email_opt_outs);
    }

    public function test_turning_the_master_off_stops_everything_but_keeps_the_choices(): void
    {
        $user = User::factory()->create(['email_preference' => 1, 'email_opt_outs' => ['note_mentioned']]);

        // Disabled switches are not submitted along with the form.
        $this->save($user, ['email_preference' => 0, 'email_templates_submitted' => 1]);

        $user->refresh();
        $this->assertFalse($user->wantsEmail('project_assigned'));
        $this->assertSame(['note_mentioned'], $user->email_opt_outs);

        // Back on: the earlier choice is still there, nothing else was switched off.
        $this->save($user, [
            'email_preference' => 1,
            'email_templates_submitted' => 1,
            'email_templates' => array_fill_keys(array_diff(NotificationTemplateService::userSwitchableKeys(), ['note_mentioned']), 1),
        ]);

        $user->refresh();
        $this->assertTrue($user->wantsEmail('project_assigned'));
        $this->assertFalse($user->wantsEmail('note_mentioned'));
    }

    public function test_saving_a_form_that_carried_no_switches_changes_nothing(): void
    {
        $user = User::factory()->create(['email_preference' => 1, 'email_opt_outs' => ['note_mentioned']]);

        $this->save($user, ['email_preference' => 1])->assertSessionHasNoErrors();

        $this->assertSame(['note_mentioned'], $user->fresh()->email_opt_outs);
    }
}
