<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\ModuleType;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Route-model binding trusts whatever project id the request carries. These
 * tests hold the project endpoints to the same visibility the projects list
 * already shows the user — and hold update() to the fields the edit form has,
 * because Project::$guarded is empty.
 */
class ProjectAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string ...$roles): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create(['user_type_id' => 1]);

        $user->syncRoles(array_map(
            fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']),
            $roles
        ));

        return $user->fresh();
    }

    /**
     * Enough of a project for ProjectController::show() to render: the page reads
     * the latest active task, the customer's finance option, and looks up the
     * Service Manager role for its ticket panel.
     */
    private function project(string $name = 'Someone Elses Project'): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        Department::firstOrCreate(['id' => 5], ['name' => 'Installation']);
        SubDepartment::firstOrCreate(['id' => 1], ['name' => 'New Deals', 'department_id' => 1]);
        Role::firstOrCreate(['name' => 'Service Manager', 'guard_name' => 'web']);

        $inverter = InverterType::create(['name' => 'IQ8']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
            'internal_base_cost' => 800,
            'internal_labor_cost' => 200,
        ]);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sales_partner_id' => SalesPartner::create(['name' => 'Scope Partner'])->id,
            // The cost panel a full-access user sees reads both of these.
            'module_type_id' => ModuleType::create(['name' => 'CS6R-400MS', 'value' => 400, 'internal_module_cost' => 100])->id,
            'inverter_type_id' => $inverter->id,
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => FinanceOption::firstOrCreate(
                ['name' => 'Cash'],
                ['loan_id' => 0, 'holdback' => 0, 'dollar_watt_value' => 0, 'pto_restriction' => 0, 'no_of_days' => 0]
            )->id,
            'contract_amount' => 30000,
            'redline_costs' => 20000,
            'adders' => 0,
            'commission' => 2000,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $project = Project::create([
            'project_name' => $name,
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'budget' => 30000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'actual_material_cost' => 1000,
        ]);

        Task::create([
            'project_id' => $project->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
        ]);

        return $project;
    }

    /** Owning the project's latest active task is what puts an Employee in scope. */
    private function assignTo(User $user, Project $project): void
    {
        $employee = Employee::create(['name' => $user->name, 'user_id' => $user->id]);

        Task::where('project_id', $project->id)->update(['employee_id' => $employee->id]);
    }

    public function test_an_employee_cannot_open_a_project_that_is_not_theirs(): void
    {
        $project = $this->project();

        $this->actingAs($this->user('Employee'))
            ->get(route('projects.show', $project->id))
            ->assertForbidden();
    }

    public function test_an_employee_cannot_rewrite_a_project_that_is_not_theirs(): void
    {
        $project = $this->project();

        $this->actingAs($this->user('Employee'))
            ->putJson('/projects/'.$project->id, [
                'project_name' => 'HIJACKED',
                'budget' => 1,
                'customer_id' => $project->customer_id,
                'start_date' => '2026-01-01',
                'end_date' => '2026-02-01',
            ])
            ->assertForbidden();

        $this->assertSame('Someone Elses Project', $project->refresh()->project_name);
    }

    public function test_an_employee_cannot_move_a_project_that_is_not_theirs(): void
    {
        $project = $this->project();

        $this->actingAs($this->user('Employee'))
            ->postJson(route('move.project'), [
                'projectId' => $project->id,
                'departmentId' => 5,
                'subDepartmentId' => 1,
            ])
            ->assertForbidden();

        $this->assertSame(1, (int) $project->refresh()->department_id);
    }

    public function test_an_employee_can_open_their_own_project(): void
    {
        $project = $this->project();
        $employee = $this->user('Employee');
        $this->assignTo($employee, $project);

        $this->actingAs($employee)->get(route('projects.show', $project->id))->assertOk();
    }

    /**
     * The screens read only a user's first role, so a gate that did the same
     * would lock a Super Admin who also carries a narrow role out of their own
     * CRM. The widest role has to win.
     */
    public function test_a_super_admin_who_also_holds_a_narrow_role_is_not_locked_out(): void
    {
        $project = $this->project();

        // The edit screen goes through the same gate with far less to render than
        // the full project page.
        $this->actingAs($this->user('Employee', 'Super Admin'))
            ->get(route('projects.edit', $project->id))
            ->assertOk();
    }

    public function test_update_writes_only_the_fields_the_edit_form_offers(): void
    {
        $project = $this->project();

        $this->actingAs($this->user('Super Admin'))
            ->putJson('/projects/'.$project->id, [
                'project_name' => 'Renamed',
                'budget' => 500,
                'customer_id' => $project->customer_id,
                'start_date' => '2026-01-01',
                'end_date' => '2026-02-01',
                // Not on the form: a lane jump past every move gate, and a
                // rewrite of the project's cost figures.
                'department_id' => 5,
                'actual_material_cost' => 1,
            ])
            ->assertOk();

        $project->refresh();
        $this->assertSame('Renamed', $project->project_name);
        $this->assertSame(1, (int) $project->department_id);
        $this->assertEquals(1000, $project->actual_material_cost);
    }

    public function test_update_rejects_input_the_form_would_never_send(): void
    {
        $project = $this->project();

        $this->actingAs($this->user('Super Admin'))
            ->putJson('/projects/'.$project->id, [
                'project_name' => '',
                'budget' => 'not-a-number',
                'customer_id' => 999999,
                'start_date' => 'yesterday-ish',
                'end_date' => '2020-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['project_name', 'budget', 'customer_id', 'start_date']);
    }
}
