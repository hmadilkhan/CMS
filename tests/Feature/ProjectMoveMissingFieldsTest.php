<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A project may not leave a department until that department's required fields
 * are filled in. The refusal has to NAME those fields the way the project form
 * labels them - an unreadable "missing required fields4" left the user with
 * nothing to act on.
 */
class ProjectMoveMissingFieldsTest extends TestCase
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
     * A project in Permitting with Installation staffed, so the move is only
     * ever stopped by the required-field check.
     *
     * @return array{project: Project, permitting: Department, installation: Department, installLane: SubDepartment, task: Task}
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

        $salesPartner = SalesPartner::create(['name' => 'Fields Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Fields',
            'last_name' => 'Customer',
            'street' => '9 Permit Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-333-3333',
            'email' => 'fields.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 14,
            'inverter_qty' => 1,
        ]);

        $project = Project::create(array_merge([
            'customer_id' => $customer->id,
            'department_id' => $permitting->id,
            'sub_department_id' => $permittingLane->id,
            'project_name' => 'Missing Fields Project',
            'code' => '9200',
            'budget' => 28000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            // Past the NTP gate, which sits after this check.
            'ntp_approval_date' => '2026-07-01',
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

    private function requirePermittingFields(array $fields): void
    {
        foreach ($fields as $field) {
            DB::table('project_department_fields')->insert([
                'department_id' => 4,
                'field_name' => $field,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function moveToInstallation(array $fixture)
    {
        return $this->actingAs($this->superAdmin())->postJson(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'taskId' => $fixture['task']->id,
            'departmentId' => $fixture['installation']->id,
            'subDepartmentId' => $fixture['installLane']->id,
        ]);
    }

    public function test_the_refusal_names_the_missing_fields_and_the_department(): void
    {
        $this->requirePermittingFields(['permitting_approval_date', 'fire_review_required']);
        $fixture = $this->fixture(['fire_review_required' => null]);

        $response = $this->moveToInstallation($fixture)
            ->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'requires' => 'department_fields',
                'department_name' => 'Permitting',
            ]);

        $this->assertEqualsCanonicalizing(
            ['Permit Approval Date', 'Fire Review Required'],
            $response->json('missing_field_labels')
        );

        $error = $response->json('error');

        $this->assertStringContainsString('Permitting', $error);
        $this->assertStringContainsString('Permit Approval Date', $error);
        $this->assertStringContainsString('Fire Review Required', $error);
        // The raw column name and the bare department id are gone.
        $this->assertStringNotContainsString('permitting_approval_date', $error);
        $this->assertStringNotContainsString('department.4', $error);

        $this->assertSame(
            $fixture['permitting']->id,
            (int) $fixture['project']->refresh()->department_id
        );
    }

    public function test_only_the_empty_half_of_a_paired_requirement_is_listed(): void
    {
        $this->requirePermittingFields(['hoa_approval_request_date', 'hoa_approval_date']);
        $fixture = $this->fixture([
            'fire_review_required' => 0,
            'hoa' => 'yes',
            'hoa_approval_request_date' => '2026-07-10',
        ]);

        $this->moveToInstallation($fixture)
            ->assertStatus(422)
            ->assertJson(['missing_field_labels' => ['HOA Approval Date']]);
    }

    public function test_a_project_with_every_required_field_filled_moves(): void
    {
        $this->requirePermittingFields(['permitting_approval_date', 'fire_review_required']);
        $fixture = $this->fixture([
            'fire_review_required' => 0,
            'permitting_approval_date' => '2026-07-15',
        ]);

        $this->moveToInstallation($fixture)
            ->assertOk()
            ->assertJson(['status' => 200]);

        $this->assertSame(
            $fixture['installation']->id,
            (int) $fixture['project']->refresh()->department_id
        );
    }
}
