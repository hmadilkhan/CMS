<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Livewire\ReportRunner;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\FinanceOption;
use App\Models\Project;
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
 * Two levels of grouping, and a choice of what each numeric column says in the
 * subtotal rows. The averages are the reason every level is measured by its own
 * query: an average of averages is not an average.
 */
class ReportSummariesTest extends TestCase
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

    private function deal(FinanceOption $option, int $departmentId, string $name, float $amount): void
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

        Project::create([
            'project_name' => $name.' Project',
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => $departmentId,
            'sub_department_id' => 1,
        ]);
    }

    /** Cash: 10k in Deal Review, 30k in Permitting. Loan: 20k in Deal Review. */
    private function pipeline(): array
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        Department::firstOrCreate(['id' => 2], ['name' => 'Permitting']);
        SubDepartment::firstOrCreate(['id' => 1], ['name' => 'New Deals', 'department_id' => 1]);

        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);

        $this->deal($cash, 1, 'Cash Small', 10000);
        $this->deal($cash, 2, 'Cash Big', 30000);
        $this->deal($loan, 1, 'Loan', 20000);

        return [$cash, $loan];
    }

    private function builder(User $user)
    {
        return Livewire::actingAs($user)
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name');
    }

    public function test_a_second_level_nests_inside_the_first(): void
    {
        $this->pipeline();

        $groups = $this->builder($this->user())
            ->set('groupBy2', 'departments.name')
            ->call('refreshPreview')
            ->get('previewGroups');

        $this->assertCount(2, $groups);
        $this->assertSame('Cash', $groups[0]['value']);
        $this->assertSame(2, $groups[0]['count']);
        $this->assertEquals(40000, $groups[0]['aggregates']['contract_amount']);

        $inner = $groups[0]['children'];
        $this->assertCount(2, $inner);
        $this->assertSame('Deal Review', $inner[0]['value']);
        $this->assertEquals(10000, $inner[0]['aggregates']['contract_amount']);
        $this->assertSame('Permitting', $inner[1]['value']);
        $this->assertEquals(30000, $inner[1]['aggregates']['contract_amount']);

        // detail rows hang on the deepest level, not on the outer one
        $this->assertSame([], $groups[0]['rows']);
        $this->assertCount(1, $inner[0]['rows']);
    }

    public function test_dropping_the_first_level_drops_the_second(): void
    {
        $this->pipeline();

        $component = $this->builder($this->user())
            ->set('groupBy2', 'departments.name')
            ->set('groupBy', '');

        $this->assertSame('', $component->get('groupBy2'));
        $this->assertSame([], $component->get('previewGroups'));
    }

    public function test_a_column_can_be_averaged_instead_of_summed(): void
    {
        $this->pipeline();

        $component = $this->builder($this->user())
            ->call('setColumnSummary', 'customer_finances.contract_amount', 'avg');

        $groups = $component->get('previewGroups');

        $this->assertEquals(20000, $groups[0]['aggregates']['contract_amount']);

        // the report's own average is of every record, not of the two group averages
        $this->assertEquals(20000, $component->get('previewTotals')['aggregates']['contract_amount']);
    }

    public function test_the_report_average_is_not_an_average_of_averages(): void
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        SubDepartment::firstOrCreate(['id' => 1], ['name' => 'New Deals', 'department_id' => 1]);

        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);

        // Cash averages 10, from three records; the loan group is one 100.
        $this->deal($cash, 1, 'A', 10);
        $this->deal($cash, 1, 'B', 10);
        $this->deal($cash, 1, 'C', 10);
        $this->deal($loan, 1, 'D', 100);

        $totals = $this->builder($this->user())
            ->call('setColumnSummary', 'customer_finances.contract_amount', 'avg')
            ->get('previewTotals');

        // (10+10+10+100)/4 = 32.5, not (10+100)/2 = 55
        $this->assertEquals(32.5, $totals['aggregates']['contract_amount']);
    }

    public function test_lowest_and_highest_are_offered_too(): void
    {
        $this->pipeline();

        $lowest = $this->builder($this->user())
            ->call('setColumnSummary', 'customer_finances.contract_amount', 'min')
            ->get('previewGroups');
        $this->assertEquals(10000, $lowest[0]['aggregates']['contract_amount']);

        $highest = $this->builder($this->user())
            ->call('setColumnSummary', 'customer_finances.contract_amount', 'max')
            ->get('previewGroups');
        $this->assertEquals(30000, $highest[0]['aggregates']['contract_amount']);
    }

    public function test_a_column_can_be_left_out_of_the_summaries(): void
    {
        $this->pipeline();

        $groups = $this->builder($this->user())
            ->call('setColumnSummary', 'customer_finances.contract_amount', 'none')
            ->get('previewGroups');

        $this->assertSame([], $groups[0]['aggregates']);
        $this->assertSame(2, $groups[0]['count']);
    }

    public function test_both_levels_and_the_summaries_are_saved_and_reloaded(): void
    {
        $this->pipeline();
        $user = $this->user();

        $this->builder($user)
            ->set('reportName', 'Plan then lane')
            ->set('groupBy2', 'departments.name')
            ->call('setColumnSummary', 'customer_finances.contract_amount', 'avg')
            ->call('saveReport');

        $report = SavedReport::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('finance_options.name', $report->group_by);
        $this->assertSame('departments.name', $report->group_by_2);
        $this->assertSame(['customer_finances.contract_amount' => 'avg'], $report->summaries);

        $reloaded = Livewire::actingAs($user)
            ->test(DynamicReportBuilder::class)
            ->call('loadReportForEdit', $report->id);

        $this->assertSame('departments.name', $reloaded->get('groupBy2'));
        $this->assertSame('avg', $reloaded->get('columnSummaries')['customer_finances.contract_amount']);
    }

    public function test_the_runner_shows_both_levels_and_the_saved_summary(): void
    {
        $this->pipeline();
        $user = $this->user();

        $report = SavedReport::create([
            'name' => 'Plan then lane',
            'report_type' => 'Plan then lane',
            'selected_fields' => ['customers.first_name', 'customer_finances.contract_amount'],
            'group_by' => 'finance_options.name',
            'group_by_2' => 'departments.name',
            'summaries' => ['customer_finances.contract_amount' => 'avg'],
            'filters' => [],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->call('runReport');

        $groups = $component->get('groups');

        $this->assertEquals(20000, $groups[0]['aggregates']['contract_amount']);
        $this->assertSame('Deal Review', $groups[0]['children'][0]['value']);
        $this->assertEquals(10000, $groups[0]['children'][0]['aggregates']['contract_amount']);

        // the chart names the measure it is actually drawing
        $this->assertStringContainsString('average', $component->get('chart')['measure']);
    }

    public function test_the_same_field_cannot_be_both_levels(): void
    {
        $this->pipeline();

        $component = $this->builder($this->user())
            ->set('groupBy2', 'finance_options.name')
            ->call('refreshPreview');

        $this->assertCount(1, $component->instance()->groupFields());
        $this->assertSame([], $component->get('previewGroups')[0]['children']);
    }
}
