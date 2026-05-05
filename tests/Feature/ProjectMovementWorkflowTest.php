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

class ProjectMovementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        return $user;
    }

    private function movementFixture(bool $withTargetEmployee = true): array
    {
        $sourceDepartment = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $targetDepartment = Department::create(['id' => 2, 'name' => 'Engineering']);

        $sourceSubDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $sourceDepartment->id,
            'name' => 'New Deals',
        ]);
        $targetSubDepartment = SubDepartment::create([
            'id' => 2,
            'department_id' => $targetDepartment->id,
            'name' => 'Design Review',
        ]);

        $sourceEmployeeUser = User::factory()->create(['user_type_id' => 2]);
        $sourceEmployeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $sourceEmployee = Employee::create([
            'name' => 'Source Employee',
            'code' => 'EMP-SOURCE',
            'email' => 'source.employee@example.com',
            'phone' => '555-111-1111',
            'user_id' => $sourceEmployeeUser->id,
        ]);
        $sourceEmployee->department()->attach($sourceDepartment->id);

        $targetEmployee = null;
        if ($withTargetEmployee) {
            $targetManagerUser = User::factory()->create(['user_type_id' => 2]);
            $targetManagerUser->assignRole(Role::firstOrCreate(['name' => 'Manager']));

            $targetEmployee = Employee::create([
                'name' => 'Target Manager',
                'code' => 'EMP-TARGET',
                'email' => 'target.manager@example.com',
                'phone' => '555-222-2222',
                'user_id' => $targetManagerUser->id,
            ]);
            $targetEmployee->department()->attach($targetDepartment->id);
        }

        $salesPartner = SalesPartner::create(['name' => 'Movement Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Movement',
            'last_name' => 'Customer',
            'street' => '789 Movement Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-333-3333',
            'email' => 'movement.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 14,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 5600,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $sourceDepartment->id,
            'sub_department_id' => $sourceSubDepartment->id,
            'project_name' => 'Movement Project',
            'budget' => 28000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $sourceEmployee->id,
            'department_id' => $sourceDepartment->id,
            'sub_department_id' => $sourceSubDepartment->id,
            'user_id' => $sourceEmployeeUser->id,
        ]);

        return compact(
            'sourceDepartment',
            'targetDepartment',
            'sourceSubDepartment',
            'targetSubDepartment',
            'sourceEmployee',
            'targetEmployee',
            'project',
            'task'
        );
    }

    public function test_project_move_completes_current_task_and_assigns_next_task_to_target_department_manager(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();

        $response = $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'Ready for engineering.',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Project Moved Successfully',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['targetDepartment']->id,
            'sub_department_id' => $fixture['targetSubDepartment']->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $fixture['task']->id,
            'status' => 'Completed',
            'notes' => 'Ready for engineering.',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $fixture['project']->id,
            'employee_id' => $fixture['targetEmployee']->id,
            'department_id' => $fixture['targetDepartment']->id,
            'sub_department_id' => $fixture['targetSubDepartment']->id,
            'status' => 'In-Progress',
            'user_id' => $admin->id,
        ]);
    }

    public function test_project_move_blocks_forward_move_when_required_department_fields_are_missing(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();

        DB::table('project_department_fields')->insert([
            'department_id' => $fixture['sourceDepartment']->id,
            'field_name' => 'site_survey_link',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'Try move without required data.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'error' => 'Cannot move project. Missing required fields for the current department.1',
                'missing_fields' => ['site_survey_link'],
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $fixture['sourceSubDepartment']->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $fixture['task']->id,
            'status' => 'In-Progress',
        ]);
    }

    public function test_project_move_rolls_back_when_target_department_has_no_employee(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture(withTargetEmployee: false);

        $response = $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'No assignee exists.',
        ]);

        $response
            ->assertOk()
            ->assertJson(['status' => 500]);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $fixture['sourceSubDepartment']->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $fixture['task']->id,
            'status' => 'In-Progress',
            'notes' => null,
        ]);
        $this->assertSame(1, Task::where('project_id', $fixture['project']->id)->count());
    }
}
