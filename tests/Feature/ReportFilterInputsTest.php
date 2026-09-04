<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Livewire\ReportRunner;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\FinanceOption;
use App\Models\SavedReport;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * customer_finances stores the finance plan, loan term and APR as ids, so a
 * filter on one of them used to ask the user to type "17" for the plan they
 * know as "Cash". Those columns now offer the names to pick from.
 */
class ReportFilterInputsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'Report Builder', 'guard_name' => 'web']));
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    private function customerOn(FinanceOption $option, string $name): Customer
    {
        $customer = Customer::create(['first_name' => $name, 'last_name' => 'Doe']);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $option->id,
            'contract_amount' => 30000,
            'redline_costs' => 0,
            'adders' => '0',
            'commission' => 0,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
        ]);

        return $customer;
    }

    public function test_the_finance_option_filter_offers_the_plan_names(): void
    {
        FinanceOption::create(['name' => 'Cash']);
        FinanceOption::create(['name' => 'Sunlight 25yr']);

        Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('filterField', 'customer_finances.finance_option_id')
            ->assertSee('Cash')
            ->assertSee('Sunlight 25yr');
    }

    public function test_a_dropdown_filter_files_the_id_and_shows_the_name(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'Sunlight 25yr']);

        $this->customerOn($cash, 'Paid');
        $this->customerOn($loan, 'Financed');

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name'])
            ->set('filterField', 'customer_finances.finance_option_id')
            ->set('filterOperator', '=')
            ->set('filterValue', (string) $cash->id)
            ->call('addFilter');

        // The chip reads as the plan, not as its id.
        $component->assertSee('Cash');
        $this->assertSame('Cash', $component->get('filters')[0]['value_label']);

        $rows = $component->call('generateReport')->get('reportData');

        $this->assertCount(1, $rows);
        $this->assertSame('Paid', $rows->first()->first_name);
    }

    public function test_the_finance_plan_can_be_reported_as_a_name(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $this->customerOn($cash, 'Paid');

        $rows = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'finance_options.name'])
            ->call('generateReport')
            ->get('reportData');

        $this->assertCount(1, $rows);
        $this->assertSame('Cash', $rows->first()->finance_option_name);
    }

    public function test_an_id_dropdown_is_keyed_by_id_not_by_name(): void
    {
        $sub = SubDepartment::create(['name' => 'New Deals', 'department_id' => 1]);

        $component = Livewire::actingAs($this->user())->test(DynamicReportBuilder::class);

        $this->assertSame(
            [$sub->id => 'New Deals'],
            $component->instance()->getDropdownOptions('projects.sub_department_id')
        );

        // The name column keeps comparing against names.
        $this->assertSame(
            ['New Deals' => 'New Deals'],
            $component->instance()->getDropdownOptions('sub_departments.name')
        );
    }

    public function test_the_runner_offers_the_same_dropdown_for_a_saved_filter(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $this->customerOn($cash, 'Paid');

        $user = $this->user();

        $report = SavedReport::create([
            'name' => 'By plan',
            'report_type' => 'By plan',
            'selected_fields' => ['customers.first_name'],
            'filters' => [[
                'field' => 'customer_finances.finance_option_id',
                'operator' => '=',
                'value' => (string) $cash->id,
                'field_name' => 'Finance Option ID',
            ]],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->assertSee('Cash');
    }
}
