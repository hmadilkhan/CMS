<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Email;
use App\Models\Project;
use App\Models\SubDepartment;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * /show-website-emails is deliberately unauthenticated — the public
 * project-tracking page calls it. That makes the shape of its input the only
 * thing standing between a stranger and every customer's correspondence.
 */
class PublicEmailAccessTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithEmail(): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $project = Project::create([
            'project_name' => 'Tracked Project',
            'code' => 'P-999',
            'customer_id' => $customer->id,
            'department_id' => 1,
        ]);

        Email::create([
            'project_id' => $project->id,
            'department_id' => 1,
            'customer_id' => $customer->id,
            'subject' => 'CONFIDENTIAL contract terms',
            'body' => 'Customer bank details inside',
            'direction' => 'received',
        ]);

        return $project;
    }

    public function test_a_raw_project_id_cannot_be_used_to_read_a_project_s_emails(): void
    {
        $project = $this->projectWithEmail();

        $response = $this->post('/show-website-emails', ['project_id' => $project->id]);

        $response->assertNotFound();
        $this->assertStringNotContainsString('CONFIDENTIAL contract terms', $response->getContent());
        $this->assertStringNotContainsString('Customer bank details inside', $response->getContent());
    }

    public function test_a_forged_reference_is_rejected(): void
    {
        $this->projectWithEmail();

        $this->post('/show-website-emails', ['project_id' => 'not-an-encrypted-value'])
            ->assertNotFound();
    }

    public function test_the_encrypted_reference_the_tracking_page_sends_still_works(): void
    {
        $project = $this->projectWithEmail();

        $response = $this->post('/show-website-emails', [
            'project_id' => Crypt::encrypt($project->id),
        ]);

        $response->assertOk();
        $this->assertStringContainsString('CONFIDENTIAL contract terms', $response->getContent());
    }

    /**
     * The customer-facing page end to end: it must still render, and the
     * reference it puts in its own JavaScript must still fetch the emails.
     * A raw id is refused, so the page has to be sending the encrypted one.
     */
    public function test_the_customer_tracking_page_still_renders_and_its_email_call_works(): void
    {
        $project = $this->projectWithEmail();

        SubDepartment::firstOrCreate(['id' => 1], ['name' => 'New Deals', 'department_id' => 1]);
        Task::create([
            'project_id' => $project->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
        ]);

        // No login: this is the link a customer follows from their email.
        $page = $this->get('/track-your-project/'.Crypt::encrypt($project->code));
        $page->assertOk();

        // Pull the reference the page's own script passes to showEmails(...).
        preg_match('/showEmails\("([^"]+)"\)/', $page->getContent(), $matches);
        $this->assertNotEmpty($matches, 'The tracking page no longer calls showEmails with a reference.');

        $this->assertNotSame(
            (string) $project->id,
            $matches[1],
            'The page is still sending the raw project id.'
        );

        $this->post('/show-website-emails', ['project_id' => $matches[1]])
            ->assertOk()
            ->assertSee('CONFIDENTIAL contract terms');
    }
}
