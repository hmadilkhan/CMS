<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocumentFollowUp;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use App\Services\DocumentFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Permitting -> Installation is gated on the NTP approval date: without it the
 * move is refused and the move modal asks for the date.
 *
 * It is the same move the MPU chase intercepts, and the gate deliberately runs
 * first - see docs/follow-ups.md.
 */
class ProjectNtpApprovalGateTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        return $user;
    }

    /**
     * A project sitting in Permitting, with Installation staffed so the move can
     * actually complete.
     *
     * @return array{project: Project, permitting: Department, installation: Department, installLane: SubDepartment}
     */
    private function fixture(array $projectAttributes = []): array
    {
        $permitting = Department::create(['id' => 4, 'name' => 'Permitting']);
        $installation = Department::create(['id' => 5, 'name' => 'Installation']);

        $permittingLane = SubDepartment::create([
            'id' => 1,
            'department_id' => $permitting->id,
            'name' => 'Permit Submitted',
            'show_in_move_list' => 1,
        ]);
        $installLane = SubDepartment::create([
            'id' => 12,
            'department_id' => $installation->id,
            'name' => 'Install Not Scheduled',
            'show_in_move_list' => 1,
        ]);
        // The MPU chase's parked lane, closed to manual movement.
        SubDepartment::create([
            'id' => 31,
            'department_id' => $installation->id,
            'name' => 'Install Pending Document',
            'show_in_move_list' => 0,
        ]);

        $permittingUser = User::factory()->create(['user_type_id' => 2]);
        $permittingUser->assignRole(Role::firstOrCreate(['name' => 'Manager']));
        $permittingEmployee = Employee::create([
            'name' => 'Permitting Manager',
            'code' => 'EMP-PERMIT',
            'email' => 'permitting.manager@example.com',
            'phone' => '555-111-1111',
            'user_id' => $permittingUser->id,
        ]);
        $permittingEmployee->department()->attach($permitting->id);

        $installUser = User::factory()->create(['user_type_id' => 2]);
        $installUser->assignRole(Role::firstOrCreate(['name' => 'Manager']));
        $installEmployee = Employee::create([
            'name' => 'Install Manager',
            'code' => 'EMP-INSTALL',
            'email' => 'install.manager@example.com',
            'phone' => '555-222-2222',
            'user_id' => $installUser->id,
        ]);
        $installEmployee->department()->attach($installation->id);

        $salesPartner = SalesPartner::create(['name' => 'NTP Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Ntp',
            'last_name' => 'Customer',
            'street' => '5 Permit Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-333-3333',
            'email' => 'ntp.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 14,
            'inverter_qty' => 1,
        ]);

        $project = Project::create(array_merge([
            'customer_id' => $customer->id,
            'department_id' => $permitting->id,
            'sub_department_id' => $permittingLane->id,
            'project_name' => 'NTP Gate Project',
            'code' => '9100',
            'budget' => 28000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            // Permitting's own required field, so the move reaches the NTP gate
            // instead of stopping at the required-field check before it.
            'fire_review_required' => 0,
        ], $projectAttributes));

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $permittingEmployee->id,
            'department_id' => $permitting->id,
            'sub_department_id' => $permittingLane->id,
            'status' => 'In-Progress',
            'user_id' => $permittingUser->id,
        ]);

        return compact('project', 'permitting', 'installation', 'installLane', 'task');
    }

    private function moveToInstallation(array $fixture, array $extra = [])
    {
        return $this->actingAs($this->superAdmin())->postJson(route('move.project'), array_merge([
            'projectId' => $fixture['project']->id,
            'taskId' => $fixture['task']->id,
            'departmentId' => $fixture['installation']->id,
            'subDepartmentId' => $fixture['installLane']->id,
        ], $extra));
    }

    public function test_the_move_to_installation_is_refused_without_an_ntp_approval_date(): void
    {
        $fixture = $this->fixture();

        $this->moveToInstallation($fixture)
            ->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'requires' => 'ntp_approval_date',
                'field_label' => 'NTP Approval Date',
            ]);

        // Nothing moved.
        $this->assertSame(
            $fixture['permitting']->id,
            (int) $fixture['project']->refresh()->department_id
        );
    }

    public function test_supplying_the_date_in_the_move_request_saves_it_and_lets_the_move_through(): void
    {
        $fixture = $this->fixture();

        $this->moveToInstallation($fixture, ['ntp_approval_date' => '2026-08-20'])
            ->assertOk()
            ->assertJson(['status' => 200]);

        $project = $fixture['project']->refresh();

        $this->assertSame($fixture['installation']->id, (int) $project->department_id);
        $this->assertSame('2026-08-20', substr((string) $project->ntp_approval_date, 0, 10));
    }

    public function test_a_project_that_already_has_the_date_moves_without_being_asked(): void
    {
        $fixture = $this->fixture(['ntp_approval_date' => '2026-07-01']);

        $this->moveToInstallation($fixture)
            ->assertOk()
            ->assertJson(['status' => 200]);

        $this->assertSame(
            $fixture['installation']->id,
            (int) $fixture['project']->refresh()->department_id
        );
    }

    public function test_an_invalid_date_is_rejected_and_asked_for_again(): void
    {
        $fixture = $this->fixture();

        $this->moveToInstallation($fixture, ['ntp_approval_date' => 'not a date'])
            ->assertStatus(422)
            ->assertJson(['requires' => 'ntp_approval_date']);

        $this->assertSame(
            $fixture['permitting']->id,
            (int) $fixture['project']->refresh()->department_id
        );
    }

    public function test_other_moves_are_not_gated_by_the_ntp_approval_date(): void
    {
        $fixture = $this->fixture();

        // Permitting -> back to a second Permitting lane: not the gated move.
        $otherLane = SubDepartment::create([
            'id' => 2,
            'department_id' => $fixture['permitting']->id,
            'name' => 'Permit Approved',
            'show_in_move_list' => 1,
        ]);

        $this->actingAs($this->superAdmin())->postJson(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'taskId' => $fixture['task']->id,
            'departmentId' => $fixture['permitting']->id,
            'subDepartmentId' => $otherLane->id,
        ])->assertOk()->assertJson(['status' => 200]);
    }

    public function test_the_ntp_date_is_collected_before_the_mpu_chase_parks_the_project(): void
    {
        $fixture = $this->fixture(['mpu_required' => 'yes']);

        ProjectDocumentFollowUp::create([
            'project_id' => $fixture['project']->id,
            'type' => DocumentFollowUpService::TYPE_MPU,
            'status' => 'Pending',
            'opened_at' => now(),
        ]);

        // NTP wins the first round: the move is refused, not parked.
        $this->moveToInstallation($fixture)
            ->assertStatus(422)
            ->assertJson(['requires' => 'ntp_approval_date']);

        $this->assertSame(
            $fixture['permitting']->id,
            (int) $fixture['project']->refresh()->department_id
        );

        // With the date supplied the move runs, and now the MPU chase parks it.
        $this->moveToInstallation($fixture, ['ntp_approval_date' => '2026-08-20'])
            ->assertOk()
            ->assertJson(['status' => 200]);

        $project = $fixture['project']->refresh();

        $this->assertSame($fixture['installation']->id, (int) $project->department_id);
        $this->assertSame('2026-08-20', substr((string) $project->ntp_approval_date, 0, 10));
        $this->assertSame(31, (int) $project->sub_department_id, 'The MPU chase should have parked the project.');
    }
}
