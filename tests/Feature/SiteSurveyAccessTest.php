<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SiteSurvey;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Starting and completing a site survey are the technician's own acts, recorded
 * with timestamps and an activity-log entry. Only the technician the visit is
 * assigned to may make them.
 */
class SiteSurveyAccessTest extends TestCase
{
    use RefreshDatabase;

    private function technician(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $user->syncRoles([Role::firstOrCreate(['name' => 'Technician', 'guard_name' => 'web'])]);

        return $user->fresh();
    }

    private function survey(User $technician): SiteSurvey
    {
        Department::firstOrCreate(['id' => 2], ['name' => 'Site Survey']);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sales_partner_id' => SalesPartner::create(['name' => 'Survey Partner'])->id,
        ]);

        $project = Project::create([
            'project_name' => 'Survey Project',
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => 2,
        ]);

        return SiteSurvey::create([
            'project_id' => $project->id,
            'technician_id' => $technician->id,
            'survey_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'customer_address' => '12 Solar Rd',
            'status' => 'scheduled',
        ]);
    }

    public function test_another_technician_cannot_start_someone_elses_survey(): void
    {
        $survey = $this->survey($this->technician());

        $this->actingAs($this->technician())
            ->postJson(route('site-surveys.start', $survey->id))
            ->assertForbidden();

        $this->assertSame('scheduled', $survey->refresh()->status);
    }

    public function test_another_technician_cannot_complete_someone_elses_survey(): void
    {
        $survey = $this->survey($this->technician());

        $this->actingAs($this->technician())
            ->postJson(route('site-surveys.complete', $survey->id), ['notes' => 'done'])
            ->assertForbidden();

        $this->assertSame('scheduled', $survey->refresh()->status);
    }

    public function test_the_assigned_technician_can_start_and_complete_their_survey(): void
    {
        $technician = $this->technician();
        $survey = $this->survey($technician);

        $this->actingAs($technician)
            ->postJson(route('site-surveys.start', $survey->id))
            ->assertOk();
        $this->assertSame('in_progress', $survey->refresh()->status);

        $this->actingAs($technician)
            ->postJson(route('site-surveys.complete', $survey->id), ['notes' => 'All good'])
            ->assertOk();
        $this->assertSame('completed', $survey->refresh()->status);
    }
}
