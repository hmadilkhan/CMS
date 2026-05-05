<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LaborCost;
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

class CustomerProjectWorkflowTest extends TestCase
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

    private function seedProjectBasics(): array
    {
        $department = Department::create([
            'id' => 1,
            'name' => 'Deal Review',
        ]);
        $subDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $department->id,
            'name' => 'New Deals',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));
        Role::firstOrCreate(['name' => 'Service Manager']);

        $employee = Employee::create([
            'id' => 1,
            'name' => 'Workflow Employee',
            'code' => 'EMP-001',
            'email' => 'employee@example.com',
            'phone' => '555-100-1000',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $salesPartner = SalesPartner::create(['name' => 'Workflow Sales Partner']);

        $salesUser = User::factory()->create([
            'user_type_id' => 3,
            'sales_partner_id' => $salesPartner->id,
        ]);
        $salesUser->assignRole(Role::firstOrCreate(['name' => 'Sales Person']));

        $financeOption = FinanceOption::create([
            'name' => 'Cash',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
        ]);

        $inverter = InverterType::create(['name' => 'Workflow Inverter']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
            'internal_base_cost' => 800,
            'internal_labor_cost' => 200,
        ]);

        LaborCost::create(['cost' => 50]);

        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Workflow Module',
            'value' => 400,
            'amount' => 120,
            'internal_module_cost' => 80,
        ]);

        return compact(
            'department',
            'subDepartment',
            'employee',
            'salesPartner',
            'salesUser',
            'financeOption',
            'inverter',
            'module'
        );
    }

    private function validCustomerPayload(array $basics): array
    {
        return [
            'first_name' => 'Workflow',
            'last_name' => 'Customer',
            'street' => '123 Solar St',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85001',
            'phone' => '555-200-2000',
            'email' => 'workflow.customer@example.com',
            'panel_qty' => 10,
            'sold_date' => now()->toDateString(),
            'sales_partner_id' => $basics['salesPartner']->id,
            'sales_partner_user_id' => $basics['salesUser']->id,
            'inverter_type_id' => $basics['inverter']->id,
            'module_type_id' => $basics['module']->id,
            'inverter_qty' => 1,
            'module_qty' => 4000,
            'finance_option_id' => $basics['financeOption']->id,
            'contract_amount' => 25000,
            'redline_costs' => 18000,
            'commission' => 1500,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'adders_amount' => 0,
            'overwrite_base_price' => 0,
            'overwrite_panel_price' => 0,
            'sold_production_value' => 5000,
            'adu' => 0,
            'notes' => 'Workflow test customer.',
        ];
    }

    public function test_customer_creation_creates_customer_finance_project_and_initial_task(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $response = $this->actingAs($admin)->post(route('customers.store'), $this->validCustomerPayload($basics));

        $this->assertTrue($response->isRedirect(), $response->getContent());
        $response->assertRedirect(route('customers.index'));

        $customer = Customer::where('email', 'workflow.customer@example.com')->first();
        $this->assertNotNull($customer);

        $this->assertDatabaseHas('customer_finances', [
            'customer_id' => $customer->id,
            'finance_option_id' => $basics['financeOption']->id,
        ]);

        $project = Project::where('customer_id', $customer->id)->first();
        $this->assertNotNull($project);
        $this->assertSame('Workflow-Customer', $project->project_name);
        $this->assertSame($basics['subDepartment']->id, $project->sub_department_id);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'employee_id' => 1,
            'department_id' => $basics['department']->id,
            'sub_department_id' => $basics['subDepartment']->id,
            'status' => 'In-Progress',
        ]);
    }

    public function test_customer_creation_requires_core_fields(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('customers.store'), []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'street',
            'city',
            'state',
            'zipcode',
            'phone',
            'email',
            'panel_qty',
            'sold_date',
            'sales_partner_id',
            'inverter_type_id',
            'module_type_id',
            'inverter_qty',
            'finance_option_id',
            'contract_amount',
            'redline_costs',
            'commission',
            'dealer_fee',
            'sales_partner_user_id',
        ]);
    }

    public function test_project_creation_creates_initial_task_and_project_detail_opens(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $customer = Customer::create([
            'first_name' => 'Project',
            'last_name' => 'Customer',
            'street' => '456 Project Ave',
            'city' => 'Tempe',
            'state' => 'AZ',
            'zipcode' => '85281',
            'phone' => '555-300-3000',
            'email' => 'project.customer@example.com',
            'sales_partner_id' => $basics['salesPartner']->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_type_id' => $basics['inverter']->id,
            'module_type_id' => $basics['module']->id,
            'inverter_qty' => 1,
            'module_value' => 4800,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $basics['financeOption']->id,
            'contract_amount' => 30000,
            'redline_costs' => 20000,
            'adders' => 0,
            'commission' => 2000,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $response = $this->actingAs($admin)->post(route('projects.store'), [
            'project_name' => 'Workflow Project',
            'budget' => 30000,
            'customer_id' => $customer->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'assigntask' => $basics['employee']->id,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'messsage' => 'Project created successfully',
            ]);

        $project = Project::where('project_name', 'Workflow Project')->first();
        $this->assertNotNull($project);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'employee_id' => $basics['employee']->id,
            'department_id' => $basics['department']->id,
            'sub_department_id' => $basics['subDepartment']->id,
            'status' => 'In-Progress',
        ]);

        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();
    }
}
