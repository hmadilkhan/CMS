<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class ProjectMovementTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function setupAdmin()
    {
        UserType::create(['name' => 'Admin']);
        $user = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');
        return $user;
    }

    private function setupProjectWithDepartments()
    {
        $sourceDept = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $targetDept = Department::create(['id' => 2, 'name' => 'Engineering']);

        $sourceSubDept = SubDepartment::create([
            'id' => 1,
            'department_id' => $sourceDept->id,
            'name' => 'New Deals',
        ]);
        $targetSubDept = SubDepartment::create([
            'id' => 2,
            'department_id' => $targetDept->id,
            'name' => 'Design Review',
        ]);

        $sourceEmpUser = User::factory()->create(['user_type_id' => 2]);
        Role::create(['name' => 'Employee']);
        $sourceEmpUser->assignRole('Employee');

        $sourceEmployee = Employee::create([
            'name' => 'Source Employee',
            'code' => 'EMP-SOURCE',
            'email' => 'source@test.com',
            'phone' => '555-111-1111',
            'user_id' => $sourceEmpUser->id,
        ]);
        $sourceEmployee->department()->attach($sourceDept->id);

        $targetEmpUser = User::factory()->create(['user_type_id' => 2]);
        Role::create(['name' => 'Manager']);
        $targetEmpUser->assignRole('Manager');

        $targetEmployee = Employee::create([
            'name' => 'Target Manager',
            'code' => 'EMP-TARGET',
            'email' => 'target@test.com',
            'phone' => '555-222-2222',
            'user_id' => $targetEmpUser->id,
        ]);
        $targetEmployee->department()->attach($targetDept->id);

        $salesPartner = SalesPartner::create(['name' => 'Movement Partner']);
        $customer = Customer::create([
            'first_name' => 'Movement',
            'last_name' => 'Customer',
            'street' => '789 Movement Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-333-3333',
            'email' => 'movement@example.com',
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
            'department_id' => $sourceDept->id,
            'sub_department_id' => $sourceSubDept->id,
            'project_name' => 'Movement Test Project',
            'budget' => 28000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $sourceEmployee->id,
            'department_id' => $sourceDept->id,
            'sub_department_id' => $sourceSubDept->id,
            'user_id' => $sourceEmpUser->id,
        ]);

        return compact('sourceDept', 'targetDept', 'sourceSubDept', 'targetSubDept', 'project', 'task');
    }

    public function test_admin_can_move_project_between_departments()
    {
        $admin = $this->setupAdmin();
        $data = $this->setupProjectWithDepartments();

        $this->browse(function (Browser $browser) use ($admin, $data) {
            $browser->loginAs($admin)
                    ->visit("/projects/{$data['project']->id}")
                    ->pause(1000)
                    
                    // Move project
                    ->select('department_id', $data['targetDept']->id)
                    ->pause(500)
                    ->select('sub_department_id', $data['targetSubDept']->id)
                    ->type('notes', 'Moving to engineering for design review')
                    ->press('Move Project')
                    ->pause(2000)
                    ->assertSee('moved successfully');
        });
    }

    public function test_project_list_shows_all_projects()
    {
        $admin = $this->setupAdmin();
        $data = $this->setupProjectWithDepartments();

        $this->browse(function (Browser $browser) use ($admin, $data) {
            $browser->loginAs($admin)
                    ->visit('/projects')
                    ->pause(1000)
                    ->assertSee('Movement Test Project')
                    ->assertSee($data['sourceDept']->name);
        });
    }

    public function test_project_detail_page_loads_correctly()
    {
        $admin = $this->setupAdmin();
        $data = $this->setupProjectWithDepartments();

        $this->browse(function (Browser $browser) use ($admin, $data) {
            $browser->loginAs($admin)
                    ->visit("/projects/{$data['project']->id}")
                    ->pause(1000)
                    ->assertSee('Movement Test Project')
                    ->assertSee('Movement Customer')
                    ->assertSee('Deal Review');
        });
    }
}
