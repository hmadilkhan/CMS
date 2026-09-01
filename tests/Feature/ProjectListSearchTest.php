<?php

namespace Tests\Feature;

use App\Http\Controllers\ProjectController;
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
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The projects board's search box.
 *
 * The Pre-Inspection (ghost) lane is filled by ProjectController::ghostProjects(),
 * a query of its own — it used to ignore the search term entirely, so a project
 * sitting in that lane could not be found from the search box while every other
 * lane answered normally.
 */
class ProjectListSearchTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        // syncRoles, not assignRole: the user factory hands every user the
        // Employee role, and projectQuery() branches on getRoleNames()[0].
        $user->syncRoles([Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'])]);

        return $user->fresh();
    }

    /**
     * A ghost project: it reached Permitting (department 4) and never went past
     * department 7, which is what ghostProjects() looks for.
     */
    private function ghostProject(string $projectName, string $lastName): Project
    {
        Department::firstOrCreate(['id' => 4], ['name' => 'Permitting']);
        SubDepartment::firstOrCreate(['id' => 9], ['name' => 'Permit Submitted', 'department_id' => 4]);

        $salesPartner = SalesPartner::firstOrCreate(['name' => 'Ghost Sales Partner']);

        $customer = Customer::create([
            'first_name' => 'Ghost',
            'last_name' => $lastName,
            'street' => '12 Ghost Ave',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-000-0000',
            'email' => strtolower($lastName).'@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->subYear()->toDateString(),
            'panel_qty' => 14,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 5600,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => 4,
            'sub_department_id' => 9,
            'project_name' => $projectName,
            'budget' => 28000,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $employee = Employee::create([
            'name' => 'Ghost Employee '.$lastName,
            'code' => 'EMP-'.strtoupper($lastName),
            'email' => 'employee.'.strtolower($lastName).'@example.com',
            'phone' => '555-999-9999',
        ]);

        Task::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'department_id' => 4,
            'sub_department_id' => 9,
        ]);

        return $project;
    }

    private function ghostProjectsFor(array $query): array
    {
        $controller = app(ProjectController::class);

        return $controller->ghostProjects(new Request($query))
            ->pluck('project_name')
            ->all();
    }

    public function test_ghost_lane_answers_the_search_box(): void
    {
        $this->actingAs($this->superAdmin());

        $this->ghostProject('Alpha Ridge', 'Alvarez');
        $this->ghostProject('Beta Canyon', 'Bennett');

        $this->assertSame(['Alpha Ridge'], $this->ghostProjectsFor(['search' => 'Alpha Ridge']));
        $this->assertSame(['Alpha Ridge'], $this->ghostProjectsFor(['search' => 'alpha']));
    }

    public function test_ghost_lane_search_matches_the_customer_too(): void
    {
        $this->actingAs($this->superAdmin());

        $this->ghostProject('Alpha Ridge', 'Alvarez');
        $this->ghostProject('Beta Canyon', 'Bennett');

        $this->assertSame(['Beta Canyon'], $this->ghostProjectsFor(['search' => 'Bennett']));
    }

    public function test_ghost_lane_keeps_every_project_when_nothing_is_searched(): void
    {
        $this->actingAs($this->superAdmin());

        $this->ghostProject('Alpha Ridge', 'Alvarez');
        $this->ghostProject('Beta Canyon', 'Bennett');

        $names = $this->ghostProjectsFor([]);
        sort($names);

        $this->assertSame(['Alpha Ridge', 'Beta Canyon'], $names);
        $this->assertSame(2, count($this->ghostProjectsFor(['search' => '   '])));
    }

    /** The main lanes must keep answering the search exactly as before. */
    public function test_project_list_still_searches_the_ordinary_lanes(): void
    {
        $admin = $this->superAdmin();
        $this->ghostProject('Alpha Ridge', 'Alvarez');
        $this->ghostProject('Beta Canyon', 'Bennett');

        $this->actingAs($admin);

        $result = app(ProjectController::class)
            ->projectQuery(new Request(['id' => 'all', 'search' => 'Beta']));

        $this->assertSame(['Beta Canyon'], $result['projects']->pluck('project_name')->all());
    }
}
