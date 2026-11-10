<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\NotificationTemplate;
use App\Models\Project;
use App\Models\ServiceTicket;
use App\Services\NotificationTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NotificationTemplateTagsTest extends TestCase
{
    use RefreshDatabase;

    private function service(): NotificationTemplateService
    {
        return app(NotificationTemplateService::class);
    }

    /** A ticket whose project and customer are set in memory, so nothing is queried. */
    private function ticket(): ServiceTicket
    {
        $customer = (new Customer)->forceFill([
            'id' => 7, 'first_name' => 'Ana', 'last_name' => 'Ruiz <b>',
            'phone' => '(909) 630-2052', 'city' => 'Fresno', 'sold_date' => '2026-03-05',
        ]);
        $project = (new Project)->forceFill(['id' => 272, 'project_name' => 'Ana-Ruiz - Elm St', 'code' => '1048']);
        $project->setRelation('customer', $customer);
        $project->setRelation('department', (new Department)->forceFill(['name' => 'Permitting']));

        $ticket = (new ServiceTicket)->forceFill(['id' => 55, 'subject' => 'Inverter fault', 'priority' => 'High', 'status' => 'Open']);
        $ticket->setRelation('project', $project);

        return $ticket;
    }

    private function override(string $key, string $subject, string $body): void
    {
        NotificationTemplate::create(['key' => $key, 'subject' => $subject, 'body' => $body, 'is_active' => true]);
    }

    public function test_a_ticket_email_can_use_project_and_customer_tags(): void
    {
        $this->override(
            'service_ticket_created',
            'Ticket #{ticket_id} on {project_code}',
            '<p><a href="{project_url}">Open project</a> {customer_phone} {department_name} {sold_date} {ticket_status}</p>'
        );

        $mail = $this->service()->render('service_ticket_created', ['ticket_id' => 55], ['ticket' => $this->ticket()]);

        $this->assertSame('Ticket #55 on 1048', $mail['subject']);
        $this->assertStringContainsString('href="'.route('projects.show', 272).'"', $mail['body']);
        $this->assertStringContainsString('(909) 630-2052', $mail['body']);
        $this->assertStringContainsString('Permitting', $mail['body']);
        $this->assertStringContainsString('Mar 05, 2026', $mail['body']);
        $this->assertStringContainsString('Open', $mail['body']);
    }

    public function test_record_values_are_escaped_in_the_body_but_not_the_subject(): void
    {
        $this->override('service_ticket_created', 'For {customer_name}', '<p>{customer_name}</p>');

        $mail = $this->service()->render('service_ticket_created', [], ['ticket' => $this->ticket()]);

        $this->assertStringContainsString('Ana Ruiz &lt;b&gt;', $mail['body']);
        // The subject is plain text: strip_tags removes the stray tag instead.
        $this->assertSame('For Ana Ruiz', trim($mail['subject']));
        $this->assertStringNotContainsString('&lt;', $mail['subject']);
    }

    public function test_a_value_the_sender_passes_wins_over_the_record_tag(): void
    {
        $this->override('project_assigned', '{project_name}', '<p>{project_name} / {customer_city}</p>');
        $ticket = $this->ticket();

        $mail = $this->service()->render('project_assigned', ['project_name' => 'Ana Ruiz   Elm St'], ['project' => $ticket->project]);

        $this->assertSame('Ana Ruiz   Elm St', $mail['subject']);
        $this->assertStringContainsString('Ana Ruiz   Elm St / Fresno', $mail['body']);
    }

    public function test_a_missing_record_renders_tags_empty(): void
    {
        $this->override('service_ticket_created', 'x', '<p>[{project_url}][{customer_phone}]</p>');
        $ticket = $this->ticket()->setRelation('project', null);

        $mail = $this->service()->render('service_ticket_created', [], ['ticket' => $ticket]);

        $this->assertStringContainsString('[][]', $mail['body']);
    }

    public function test_saving_accepts_record_tags_and_still_rejects_unknown_ones(): void
    {
        $this->assertSame([], $this->service()->unknownPlaceholders('service_ticket_created', '{project_url}', '{customer_phone} {ticket_status}'));
        $this->assertSame(['frist_name'], $this->service()->unknownPlaceholders('service_ticket_created', '{frist_name}'));
        // A ticket tag does not leak into an email that has no ticket.
        $this->assertSame(['ticket_status'], $this->service()->unknownPlaceholders('project_assigned', '{ticket_status}'));
    }

    public function test_the_editor_groups_tags_and_lists_each_once(): void
    {
        $groups = $this->service()->tagGroups('service_ticket_created');

        $this->assertSame('This email', $groups[0]['label']);
        $this->assertSame(['This email', 'Service Ticket', 'Project', 'Customer'], array_column($groups, 'label'));

        $project = collect($groups)->firstWhere('label', 'Project');
        $this->assertArrayHasKey('project_url', $project['tags']);
        // project_name is the email's own placeholder, so it is not repeated under Project.
        $this->assertArrayNotHasKey('project_name', $project['tags']);
    }

    public function test_the_editor_page_offers_the_project_link_on_a_ticket_email(): void
    {
        \App\Models\UserType::firstOrCreate(['name' => 'Admin']);
        $user = \App\Models\User::factory()->create(['user_type_id' => 1]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get('/view-email-scripts?template=service_ticket_created')
            ->assertOk()
            ->assertSee('data-tag="{project_url}"', false)
            ->assertSee('data-tag="{customer_phone}"', false)
            ->assertSeeInOrder(['This email', 'Service Ticket', 'Project', 'Customer']);
    }

    public function test_every_record_and_link_tag_is_valid(): void
    {
        $records = array_keys(config('notification_tags'));

        foreach (config('notification_templates') as $key => $template) {
            foreach ($template['records'] ?? [] as $record) {
                $this->assertContains($record, $records, "{$key} lists unknown record {$record}");
            }
        }

        foreach (config('notification_tags') as $record => $definition) {
            foreach ($definition['tags'] as $name => $tag) {
                if (isset($tag['route'])) {
                    $this->assertTrue(Route::has($tag['route']), "{$record}.{$name} names a missing route");
                }
            }
        }
    }
}
