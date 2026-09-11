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
use App\Models\Zone;
use App\Services\DocumentFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The NTP approval date is chased, not demanded. Permitting -> Installation used
 * to be refused while the date was missing; the move now goes through and the
 * project waits in Install Pending Document - closed to manual moves - until the
 * funding side files the date from the Zones NTP tab. See docs/follow-ups.md.
 */
class ProjectNtpApprovalFollowUpTest extends TestCase
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

    /** The funding side, filing the date from the NTP zone tab. */
    private function fileNtpDateFromTheZone(Project $project, string $date)
    {
        $ntp = Zone::where('slug', 'ntp')->firstOrFail();
        $project->forceFill(['zone_id' => $ntp->id, 'zone_entered_at' => now()])->save();

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Funding Manager']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'View Zones', 'guard_name' => 'web']));
        $user->assignRole($role);

        return $this->actingAs($user)->postJson(route('zones.fields'), [
            'project_id' => $project->id,
            'zone_id' => $ntp->id,
            'ntp_approval_date' => $date,
        ]);
    }

    public function test_the_date_can_be_filed_while_the_project_sits_in_an_earlier_zone(): void
    {
        $fixture = $this->fixture();
        $this->moveToInstallation($fixture)->assertOk();

        // The funding side has not advanced this project: it is still in Pre NTP,
        // which is where most parked projects actually are.
        $project = $fixture['project']->refresh();
        $preNtp = Zone::where('slug', 'pre_ntp')->firstOrFail();
        $project->forceFill(['zone_id' => $preNtp->id, 'zone_entered_at' => now()])->save();

        $ntp = Zone::where('slug', 'ntp')->firstOrFail();
        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Funding Manager']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'View Zones', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->postJson(route('zones.fields'), [
            'project_id' => $project->id,
            'zone_id' => $ntp->id,
            'ntp_approval_date' => '2026-08-20',
        ])->assertOk();

        $this->assertSame(12, (int) $fixture['project']->refresh()->sub_department_id);
    }

    public function test_the_move_goes_through_and_parks_the_project_instead_of_being_refused(): void
    {
        $fixture = $this->fixture();

        $this->moveToInstallation($fixture)
            ->assertOk()
            ->assertJson(['status' => 200]);

        $project = $fixture['project']->refresh();

        $this->assertSame($fixture['installation']->id, (int) $project->department_id);
        $this->assertSame(31, (int) $project->sub_department_id, 'The project should be waiting in Install Pending Document.');
        $this->assertTrue(
            ProjectDocumentFollowUp::pending()
                ->where('project_id', $project->id)
                ->where('type', DocumentFollowUpService::TYPE_NTP_APPROVAL)
                ->exists()
        );
    }

    public function test_a_parked_project_cannot_be_moved_by_hand(): void
    {
        $fixture = $this->fixture();
        $this->moveToInstallation($fixture)->assertOk();

        $inspection = Department::create(['id' => 6, 'name' => 'Inspection']);
        $inspectionLane = SubDepartment::create([
            'id' => 16,
            'department_id' => $inspection->id,
            'name' => 'Inspection Not Scheduled',
            'show_in_move_list' => 1,
        ]);

        $task = Task::where('project_id', $fixture['project']->id)->latest('id')->firstOrFail();

        $this->actingAs($this->superAdmin())->postJson(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'taskId' => $task->id,
            'departmentId' => $inspection->id,
            'subDepartmentId' => $inspectionLane->id,
        ])->assertStatus(422);

        $this->assertSame(31, (int) $fixture['project']->refresh()->sub_department_id);
    }

    public function test_filing_the_date_from_the_zone_releases_the_project(): void
    {
        $fixture = $this->fixture();
        $this->moveToInstallation($fixture)->assertOk();

        $this->fileNtpDateFromTheZone($fixture['project']->refresh(), '2026-08-20')
            ->assertOk()
            ->assertJson(['status' => 200]);

        $project = $fixture['project']->refresh();

        $this->assertSame('2026-08-20', substr((string) $project->ntp_approval_date, 0, 10));
        $this->assertSame(12, (int) $project->sub_department_id, 'The project should be back in Install Not Scheduled.');
        $this->assertFalse(
            ProjectDocumentFollowUp::pending()
                ->where('project_id', $project->id)
                ->where('type', DocumentFollowUpService::TYPE_NTP_APPROVAL)
                ->exists()
        );
    }

    public function test_the_new_task_follows_the_project_out_of_the_parked_lane(): void
    {
        $fixture = $this->fixture();
        $this->moveToInstallation($fixture)->assertOk();
        $this->fileNtpDateFromTheZone($fixture['project']->refresh(), '2026-08-20')->assertOk();

        $task = Task::where('project_id', $fixture['project']->id)
            ->whereIn('status', ['In-Progress', 'Hold'])
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(12, (int) $task->sub_department_id);
    }

    public function test_a_project_that_already_has_the_date_is_not_parked(): void
    {
        $fixture = $this->fixture(['ntp_approval_date' => '2026-07-01']);

        $this->moveToInstallation($fixture)
            ->assertOk()
            ->assertJson(['status' => 200]);

        $project = $fixture['project']->refresh();

        $this->assertSame($fixture['installation']->id, (int) $project->department_id);
        $this->assertSame($fixture['installLane']->id, (int) $project->sub_department_id);
        $this->assertSame(
            0,
            ProjectDocumentFollowUp::where('project_id', $project->id)
                ->where('type', DocumentFollowUpService::TYPE_NTP_APPROVAL)
                ->count()
        );
    }

    public function test_other_moves_do_not_open_the_chase(): void
    {
        $fixture = $this->fixture();

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

        $this->assertSame(
            0,
            ProjectDocumentFollowUp::where('project_id', $fixture['project']->id)->count()
        );
    }

    public function test_the_ntp_date_alone_does_not_release_a_project_the_mpu_chase_is_also_holding(): void
    {
        // Both chases park in Install Pending Document, and both are owed.
        $fixture = $this->fixture(['mpu_required' => 'yes']);

        $this->moveToInstallation($fixture)->assertOk();

        $project = $fixture['project']->refresh();
        $this->assertSame(31, (int) $project->sub_department_id);

        $this->fileNtpDateFromTheZone($project, '2026-08-20')->assertOk();

        // The meter spot result is still missing, so the lane still holds it.
        $project = $fixture['project']->refresh();
        $this->assertSame(31, (int) $project->sub_department_id);
        $this->assertTrue(
            ProjectDocumentFollowUp::pending()
                ->where('project_id', $project->id)
                ->where('type', DocumentFollowUpService::TYPE_MPU)
                ->exists()
        );

        // Answering the meter spot result too lets it out.
        $project->forceFill(['meter_spot_result' => 'same'])->save();
        app(DocumentFollowUpService::class)->sync($project->refresh());

        $this->assertSame(12, (int) $fixture['project']->refresh()->sub_department_id);
    }

    public function test_parking_puts_the_project_on_the_funding_side_s_ntp_lane(): void
    {
        $fixture = $this->fixture();

        // No zone at all, which is where most of the backlog is.
        $fixture['project']->forceFill(['zone_id' => null, 'zone_entered_at' => null])->save();

        $this->moveToInstallation($fixture)->assertOk();

        $this->assertSame('NTP', $fixture['project']->refresh()->zone?->name);
    }

    public function test_a_project_the_funding_side_moved_further_along_is_not_pulled_back(): void
    {
        $fixture = $this->fixture();

        $m1 = Zone::where('slug', 'm1')->firstOrFail();
        $fixture['project']->forceFill(['zone_id' => $m1->id, 'zone_entered_at' => now()])->save();

        $this->moveToInstallation($fixture)->assertOk();

        $this->assertSame('M1', $fixture['project']->refresh()->zone?->name);
    }

    public function test_a_zone_field_nothing_is_waiting_on_is_still_read_only_from_another_zone(): void
    {
        // Date already on file, so no chase opens and nothing is owed.
        $fixture = $this->fixture(['ntp_approval_date' => '2026-07-01']);
        $this->moveToInstallation($fixture)->assertOk();

        $project = $fixture['project']->refresh();
        $preNtp = Zone::where('slug', 'pre_ntp')->firstOrFail();
        $project->forceFill(['zone_id' => $preNtp->id, 'zone_entered_at' => now()])->save();

        $ntp = Zone::where('slug', 'ntp')->firstOrFail();
        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Funding Manager']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'View Zones', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->postJson(route('zones.fields'), [
            'project_id' => $project->id,
            'zone_id' => $ntp->id,
            'ntp_approval_date' => '2026-09-09',
        ])->assertStatus(422);

        $this->assertSame('2026-07-01', substr((string) $project->refresh()->ntp_approval_date, 0, 10));
    }
}
