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

    public function test_customer_phone_is_stored_in_us_format(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $this->actingAs($admin)->post(route('customers.store'), [
            ...$this->validCustomerPayload($basics),
            'phone' => '+1 909-630-2052',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'email' => 'workflow.customer@example.com',
            'phone' => '(909) 630-2052',
        ]);
    }

    public function test_customer_phone_must_be_ten_digits(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $this->actingAs($admin)->post(route('customers.store'), [
            ...$this->validCustomerPayload($basics),
            'phone' => '62-320-4940',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('customers', ['email' => 'workflow.customer@example.com']);
    }

    public function test_editing_a_customer_with_an_old_phone_still_works_until_the_phone_is_changed(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();
        $payload = $this->validCustomerPayload($basics);

        $this->actingAs($admin)->post(route('customers.store'), $payload)->assertRedirect(route('customers.index'));

        $customer = Customer::where('email', 'workflow.customer@example.com')->firstOrFail();
        Customer::where('id', $customer->id)->update(['phone' => '62-320-4940']);

        // Phone left alone: the edit goes through and the old value is kept as it was.
        $this->actingAs($admin)
            ->put(route('customers.update', $customer), [...$payload, 'phone' => '62-320-4940', 'city' => 'Tempe'])
            ->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('Tempe', $customer->city);
        $this->assertSame('62-320-4940', $customer->phone);

        // Phone changed: it has to be a real number now.
        $this->actingAs($admin)
            ->put(route('customers.update', $customer), [...$payload, 'phone' => '62-320-4941'])
            ->assertSessionHasErrors('phone');

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), [...$payload, 'phone' => '6232049400'])
            ->assertSessionHasNoErrors();

        $this->assertSame('(623) 204-9400', $customer->fresh()->phone);
    }

    private function prepaidPpa(): FinanceOption
    {
        return FinanceOption::create([
            'name' => 'Prepaid PPA',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
        ]);
    }

    public function test_a_new_customer_enters_solar_and_battery_tpo_credits(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $this->actingAs($admin)->post(route('customers.store'), [
            ...$this->validCustomerPayload($basics),
            'finance_option_id' => $this->prepaidPpa()->id,
            'solar_tpo_credit' => 6000,
            'battery_tpo_credit' => 4000.50,
            // The form's read-only figure is not trusted - the server works it out.
            'customer_portion' => 1,
        ])->assertRedirect(route('customers.index'));

        $finance = Customer::where('email', 'workflow.customer@example.com')->firstOrFail()->finances;

        $this->assertEquals(6000, $finance->solar_tpo_credit);
        $this->assertEquals(4000.50, $finance->battery_tpo_credit);
        $this->assertEquals(10000.50, $finance->third_party_credit);
        $this->assertEquals(14999.50, $finance->customer_portion);
        $this->assertTrue($finance->tpo_split);
    }

    public function test_both_tpo_credits_are_required_for_an_option_that_splits_the_contract(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $this->actingAs($admin)->post(route('customers.store'), [
            ...$this->validCustomerPayload($basics),
            'finance_option_id' => $this->prepaidPpa()->id,
            'third_party_credit' => 5000,
        ])->assertSessionHasErrors(['solar_tpo_credit', 'battery_tpo_credit']);
    }

    public function test_a_customer_saved_before_the_split_keeps_its_single_third_party_credit(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();
        $payload = [...$this->validCustomerPayload($basics), 'finance_option_id' => $this->prepaidPpa()->id];

        $this->actingAs($admin)->post(route('customers.store'), [...$payload, 'solar_tpo_credit' => 0, 'battery_tpo_credit' => 0])
            ->assertRedirect(route('customers.index'));
        $customer = Customer::where('email', 'workflow.customer@example.com')->firstOrFail();

        // What a row saved before this change looks like.
        CustomerFinance::where('customer_id', $customer->id)->update([
            'third_party_credit' => 7000,
            'customer_portion' => 18000,
            'tpo_split' => false,
        ]);

        $this->actingAs($admin)->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('name="third_party_credit"', false)
            ->assertDontSee('name="solar_tpo_credit"', false);

        // Sending the split fields anyway does not turn it into a split row.
        $this->actingAs($admin)->put(route('customers.update', $customer), [
            ...$payload,
            'third_party_credit' => 8000,
            'solar_tpo_credit' => 500,
            'battery_tpo_credit' => 500,
        ])->assertSessionHasNoErrors();

        $finance = CustomerFinance::where('customer_id', $customer->id)->firstOrFail();
        $this->assertEquals(8000, $finance->third_party_credit);
        $this->assertEquals(0, $finance->solar_tpo_credit);
        $this->assertEquals(0, $finance->battery_tpo_credit);
        $this->assertEquals(17000, $finance->customer_portion);
        $this->assertFalse($finance->tpo_split);
    }

    public function test_an_old_customer_without_a_credit_gets_the_split_fields(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $this->actingAs($admin)->post(route('customers.store'), $this->validCustomerPayload($basics))
            ->assertRedirect(route('customers.index'));
        $customer = Customer::where('email', 'workflow.customer@example.com')->firstOrFail();
        CustomerFinance::where('customer_id', $customer->id)->update(['tpo_split' => false]);

        $this->actingAs($admin)->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('name="solar_tpo_credit"', false)
            ->assertSee('name="battery_tpo_credit"', false)
            ->assertDontSee('name="third_party_credit"', false);
    }

    public function test_an_option_that_does_not_split_the_contract_clears_the_credits(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();
        $ppa = $this->prepaidPpa();

        $this->actingAs($admin)->post(route('customers.store'), [
            ...$this->validCustomerPayload($basics),
            'finance_option_id' => $ppa->id,
            'solar_tpo_credit' => 6000,
            'battery_tpo_credit' => 4000,
        ])->assertRedirect(route('customers.index'));
        $customer = Customer::where('email', 'workflow.customer@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), $this->validCustomerPayload($basics))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_finances', [
            'customer_id' => $customer->id,
            'third_party_credit' => 0,
            'solar_tpo_credit' => 0,
            'battery_tpo_credit' => 0,
            'customer_portion' => 0,
        ]);
    }

    public function test_the_intake_form_keeps_customer_portion_in_step_with_the_contract(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();
        $payload = [...$this->validCustomerPayload($basics), 'finance_option_id' => $this->prepaidPpa()->id];

        $this->actingAs($admin)->post(route('customers.store'), [
            ...$payload,
            'solar_tpo_credit' => 6000,
            'battery_tpo_credit' => 4000,
        ])->assertRedirect(route('customers.index'));
        $customer = Customer::where('email', 'workflow.customer@example.com')->firstOrFail();

        // Its script charges Wheelhouse's dealer fee on contract minus this credit.
        $this->actingAs($basics['salesUser'])
            ->get(route('intake-form.edit', $customer->id))
            ->assertOk()
            ->assertSee('data-tpo-credit="10000.00"', false);

        // The intake form has no credit fields: the credit on file is kept and
        // Customer Portion follows the new contract amount.
        $this->actingAs($basics['salesUser'])
            ->put(route('intake-form.update', $customer->id), [...$payload, 'contract_amount' => 30000])
            ->assertRedirect(route('intake-form.index'));

        $finance = CustomerFinance::where('customer_id', $customer->id)->firstOrFail();
        $this->assertEquals(10000, $finance->third_party_credit);
        $this->assertEquals(6000, $finance->solar_tpo_credit);
        $this->assertEquals(20000, $finance->customer_portion);

        $this->actingAs($basics['salesUser'])
            ->put(route('intake-form.update', $customer->id), $this->validCustomerPayload($basics))
            ->assertRedirect(route('intake-form.index'));

        $this->assertDatabaseHas('customer_finances', [
            'customer_id' => $customer->id,
            'third_party_credit' => 0,
            'solar_tpo_credit' => 0,
            'battery_tpo_credit' => 0,
            'customer_portion' => 0,
        ]);
    }

    /**
     * The project page's adders form holds only the adders - the finance boxes
     * sit outside it - but Enter in its Amount box submits it. That used to
     * write the absent finance fields as 0 over the deal.
     */
    public function test_submitting_the_project_adders_form_leaves_the_deal_alone(): void
    {
        $admin = $this->superAdmin();
        $basics = $this->seedProjectBasics();

        $this->actingAs($admin)->post(route('customers.store'), $this->validCustomerPayload($basics))
            ->assertRedirect(route('customers.index'));
        $customer = Customer::where('email', 'workflow.customer@example.com')->firstOrFail();
        $project = Project::where('customer_id', $customer->id)->firstOrFail();

        // Exactly what the form posts for a project with no adders yet.
        $this->actingAs($admin)->post(route('projects.adders'), [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'finance_option_id' => $basics['financeOption']->id,
            'amount' => '500',
        ])->assertRedirect(route('projects.show', $project->id));

        $this->assertDatabaseHas('customer_finances', [
            'customer_id' => $customer->id,
            'finance_option_id' => $basics['financeOption']->id,
            'contract_amount' => 25000,
            'redline_costs' => 18000,
            'commission' => 1500,
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
