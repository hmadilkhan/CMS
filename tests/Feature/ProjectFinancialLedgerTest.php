<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\FinanceOption;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Third Party Credit and Customer Portion are collected for every finance option
 * that splits the contract that way - Prepaid PPA and Wheelhouse Credit Union.
 * The project page's Financial Ledger used to ask a narrower question than the
 * customer form did (a bare `finance_option_id === 9`), so a Wheelhouse deal
 * saved both values and then showed neither.
 */
class ProjectFinancialLedgerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Both roles are outside ProjectService::NARROWED_ROLES, so the page itself
     * opens either way and only the permission decides what the ledger shows.
     */
    private function viewer(string $role = 'Finance', string ...$permissions): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => $role]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $user->assignRole($role);

        return $user->fresh();
    }

    private function financeOption(string $name): FinanceOption
    {
        return FinanceOption::firstOrCreate(
            ['name' => $name],
            ['loan_id' => 0, 'holdback' => 0, 'dollar_watt_value' => 0, 'pto_restriction' => 0, 'no_of_days' => 0]
        );
    }

    private function projectOn(FinanceOption $financeOption): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        SubDepartment::firstOrCreate(['id' => 1], ['department_id' => 1, 'name' => 'New Deals']);
        // ProjectController::show() looks this role up for the ticket panel.
        Role::firstOrCreate(['name' => 'Service Manager']);

        $customer = Customer::create([
            'first_name' => 'Ledger',
            'last_name' => 'Customer',
            'street' => '8 Ledger Way',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-222-1111',
            'email' => 'ledger.customer@example.com',
            'sales_partner_id' => SalesPartner::create(['name' => 'Ledger Sales Partner'])->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $financeOption->id,
            'contract_amount' => 30000,
            'redline_costs' => 20000,
            'adders' => 0,
            'commission' => 2000,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'third_party_credit' => 18500.25,
            'customer_portion' => 11499.75,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'project_name' => 'Ledger Project',
            'code' => '9101',
            'budget' => 30000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        // ProjectController::show() reads the project's latest active task.
        Task::create([
            'project_id' => $project->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
        ]);

        return $project;
    }

    /** @dataProvider splittingOptions */
    public function test_an_option_that_splits_the_contract_shows_both_fields(string $name): void
    {
        $project = $this->projectOn($this->financeOption($name));

        $this->actingAs($this->viewer('Finance', 'View Project', 'View Financial Details'))
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertSee('Third Party Credit')
            ->assertSee('Customer Portion')
            ->assertSee('$ 18,500.25')
            ->assertSee('$ 11,499.75');
    }

    public static function splittingOptions(): array
    {
        return [
            'Wheelhouse Credit Union' => ['Wheelhouse Credit Union'],
            'Prepaid PPA' => ['Prepaid PPA'],
        ];
    }

    public function test_an_option_that_does_not_split_the_contract_shows_neither_field(): void
    {
        $project = $this->projectOn($this->financeOption('Cash'));

        $this->actingAs($this->viewer('Finance', 'View Project', 'View Financial Details'))
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertDontSee('Third Party Credit')
            ->assertDontSee('Customer Portion');
    }

    public function test_the_pair_still_follows_the_financial_details_permission(): void
    {
        $project = $this->projectOn($this->financeOption('Wheelhouse Credit Union'));

        $this->actingAs($this->viewer('Coordinator', 'View Project'))
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertDontSee('Third Party Credit')
            ->assertDontSee('Customer Portion');
    }

    public function test_the_model_answers_for_every_option_the_customer_form_asks_about(): void
    {
        $this->assertTrue($this->financeOption('Wheelhouse Credit Union')->usesCustomerPortion());
        $this->assertTrue($this->financeOption('Prepaid PPA')->usesCustomerPortion());
        $this->assertFalse($this->financeOption('Cash')->usesCustomerPortion());
    }
}
