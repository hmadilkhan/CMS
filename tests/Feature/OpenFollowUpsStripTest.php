<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Services\DocumentFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The strip under the project page's move bar lists the paperwork follow ups
 * still open on the project, so nobody has to guess why a project is parked.
 * See docs/follow-ups.md.
 */
class OpenFollowUpsStripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Department::create(['id' => 1, 'name' => 'Deal Review']);
        Department::create(['id' => 3, 'name' => 'Engineering']);
        Department::create(['id' => 4, 'name' => 'Permitting']);
        Department::create(['id' => 5, 'name' => 'Installation']);
        Department::create(['id' => 6, 'name' => 'Inspection']);
        Department::create(['id' => 8, 'name' => 'PTO']);

        SubDepartment::create(['id' => 1, 'department_id' => 1, 'name' => 'Deal Review', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 12, 'department_id' => 5, 'name' => 'Install Not Scheduled', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 31, 'department_id' => 5, 'name' => 'Install Pending Document', 'show_in_move_list' => 0]);
        SubDepartment::create(['id' => 18, 'department_id' => 8, 'name' => 'PTO', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 32, 'department_id' => 8, 'name' => 'PTO Pending Document', 'show_in_move_list' => 0]);
    }

    private function project(array $attributes = []): Project
    {
        $salesPartner = SalesPartner::create(['name' => 'Strip Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Strip',
            'last_name' => 'Customer',
            'street' => '1 Lane St',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-111-2222',
            'email' => 'strip.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        $project = Project::create(array_merge([
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'project_name' => 'Open Follow Ups Strip',
            'code' => '9400',
            'budget' => 25000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ], $attributes));

        Task::create([
            'project_id' => $project->id,
            'department_id' => $project->department_id,
            'sub_department_id' => $project->sub_department_id,
            'status' => 'In-Progress',
        ]);

        app(DocumentFollowUpService::class)->sync($project);

        return $project->refresh();
    }

    public function test_an_open_follow_up_names_the_move_it_will_hold(): void
    {
        $project = $this->project(['utility_bill_required' => 'no']);

        $open = app(DocumentFollowUpService::class)->openFollowUpsFor($project);

        $this->assertCount(1, $open);
        $this->assertSame('Utility Bill', $open[0]['title']);
        $this->assertFalse($open[0]['holding']);
        $this->assertSame('Will hold the move into PTO', $open[0]['effect']);
        $this->assertSame('Upload the bill in the Utility Bills section', $open[0]['clears_by']);
        $this->assertSame('Deal Review', $open[0]['owner']);
    }

    public function test_a_parked_project_shows_the_follow_up_holding_it(): void
    {
        $project = $this->project([
            'utility_bill_required' => 'no',
            'department_id' => 8,
            'sub_department_id' => 32,
        ]);

        $open = app(DocumentFollowUpService::class)->openFollowUpsFor($project);

        $this->assertCount(1, $open);
        $this->assertTrue($open[0]['holding']);
        $this->assertSame('Holding the project in PTO Pending Document', $open[0]['effect']);
    }

    public function test_both_chases_sharing_a_lane_are_listed(): void
    {
        $project = $this->project([
            'mpu_required' => 'yes',
            'department_id' => 5,
            'sub_department_id' => 31,
        ]);
        $service = app(DocumentFollowUpService::class);
        $service->open($project, DocumentFollowUpService::TYPE_NTP_APPROVAL);

        $open = $service->openFollowUpsFor($project);

        $this->assertSame(['Meter Spot Result', 'NTP Approval Date'], $open->pluck('title')->all());
        $this->assertTrue($open->every(fn ($followUp) => $followUp['holding']));
    }

    public function test_a_missing_ntp_date_alone_does_not_open_a_follow_up(): void
    {
        $project = $this->project(['ntp_approval_date' => null]);
        $service = app(DocumentFollowUpService::class);

        $service->sync($project);

        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_NTP_APPROVAL));
        $this->assertTrue($service->openFollowUpsFor($project)->isEmpty());
    }

    public function test_a_cleared_follow_up_is_not_listed(): void
    {
        $project = $this->project(['utility_bill_required' => 'no']);
        $service = app(DocumentFollowUpService::class);

        $project->update(['utility_bill_required' => 'yes']);
        $service->sync($project);

        $this->assertTrue($service->openFollowUpsFor($project->refresh())->isEmpty());
    }
}
