<?php

namespace Tests\Feature;

use App\Livewire\ReportRunner;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\FinanceOption;
use App\Models\SavedReport;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The run view: the chart and the subtotals describe the whole report, while
 * the table shows a page of it. Getting that split wrong is how a report comes
 * to say "$3,000" about a $3,000,000 pipeline.
 */
class ReportRunnerResultsTest extends TestCase
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

    private function deal(FinanceOption $option, string $name, float $amount): void
    {
        $customer = Customer::create(['first_name' => $name, 'last_name' => 'Doe']);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $option->id,
            'contract_amount' => $amount,
            'redline_costs' => 0,
            'adders' => '0',
            'commission' => 0,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
        ]);
    }

    private function report(User $user, ?string $groupBy = null): SavedReport
    {
        return SavedReport::create([
            'name' => 'Pipeline',
            'report_type' => 'Pipeline',
            'selected_fields' => ['customers.first_name', 'customer_finances.contract_amount'],
            'group_by' => $groupBy,
            'filters' => [],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $user->id,
        ]);
    }

    public function test_a_grouped_report_runs_with_subtotals_a_total_and_a_chart(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);
        $this->deal($cash, 'Paid', 10000);
        $this->deal($loan, 'Financed A', 30000);
        $this->deal($loan, 'Financed B', 20000);

        $user = $this->user();
        $report = $this->report($user, 'finance_options.name');

        $component = Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->call('runReport');

        $groups = $component->get('groups');
        $this->assertCount(2, $groups);
        $this->assertEquals(10000, $groups[0]['aggregates']['contract_amount']);
        $this->assertEquals(50000, $groups[1]['aggregates']['contract_amount']);
        $this->assertEquals(60000, $component->get('totals')['aggregates']['contract_amount']);

        // biggest bar first, scaled to the largest group
        $chart = $component->get('chart');
        $this->assertSame('GoodLeap Financing', $chart['bars'][0]['label']);
        $this->assertEquals(100.0, $chart['bars'][0]['width']);
        $this->assertEquals(20.0, $chart['bars'][1]['width']);
    }

    public function test_an_ungrouped_report_has_no_chart(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $this->deal($cash, 'Paid', 10000);

        $user = $this->user();

        $component = Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $this->report($user)->id)
            ->call('runReport');

        $this->assertSame([], $component->get('chart'));
        $this->assertSame([], $component->get('groups'));
        $this->assertSame(1, $component->get('rowCount'));
    }

    public function test_rows_are_paged_while_the_count_and_subtotals_are_not(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);

        for ($i = 1; $i <= 60; $i++) {
            $this->deal($cash, 'Customer '.$i, 1000);
        }

        $user = $this->user();

        $component = Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $this->report($user, 'finance_options.name')->id)
            ->call('runReport');

        $this->assertCount(50, $component->get('reportData'));
        $this->assertSame(60, $component->get('rowCount'));
        $this->assertSame(60, $component->get('groups')[0]['count']);
        $this->assertEquals(60000, $component->get('totals')['aggregates']['contract_amount']);

        $component->call('gotoPage', 2);
        $this->assertCount(10, $component->get('reportData'));
        $this->assertSame(2, $component->get('page'));

        // and it cannot be walked past the end
        $component->call('gotoPage', 99);
        $this->assertSame(2, $component->get('page'));
    }

    public function test_a_filter_value_changes_the_run_without_changing_the_report(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);
        $this->deal($cash, 'Paid', 10000);
        $this->deal($loan, 'Financed', 30000);

        $user = $this->user();

        $report = SavedReport::create([
            'name' => 'By plan',
            'report_type' => 'By plan',
            'selected_fields' => ['customers.first_name', 'customer_finances.contract_amount'],
            'group_by' => 'finance_options.name',
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

        $component = Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->set('filterValues.0', [(string) $loan->id])
            ->call('runReport');

        $this->assertSame('GoodLeap Financing', $component->get('groups')[0]['value']);
        $this->assertEquals(30000, $component->get('totals')['aggregates']['contract_amount']);

        // the saved report is untouched
        $this->assertSame((string) $cash->id, $report->fresh()->filters[0]['value']);
    }
}
