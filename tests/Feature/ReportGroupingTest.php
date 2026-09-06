<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
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
 * A grouped report is read as "how much per finance option", so its subtotals
 * have to be the truth about every matching record - not about the rows the
 * preview happens to show.
 */
class ReportGroupingTest extends TestCase
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

    private function grouped()
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);

        $this->deal($cash, 'Paid A', 10000);
        $this->deal($cash, 'Paid B', 15000);
        $this->deal($loan, 'Financed', 30000);

        return Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name')
            ->call('refreshPreview');
    }

    public function test_rows_are_split_into_their_groups_with_subtotals(): void
    {
        $groups = $this->grouped()->get('previewGroups');

        $this->assertCount(2, $groups);
        $this->assertSame('Cash', $groups[0]['value']);
        $this->assertSame(2, $groups[0]['count']);
        $this->assertEquals(25000, $groups[0]['aggregates']['contract_amount']);
        $this->assertCount(2, $groups[0]['rows']);

        $this->assertSame('GoodLeap Financing', $groups[1]['value']);
        $this->assertEquals(30000, $groups[1]['aggregates']['contract_amount']);
    }

    public function test_the_report_carries_one_total(): void
    {
        $totals = $this->grouped()->get('previewTotals');

        $this->assertSame(3, $totals['count']);
        $this->assertEquals(55000, $totals['aggregates']['contract_amount']);
    }

    public function test_subtotals_cover_every_record_not_only_the_previewed_rows(): void
    {
        $option = FinanceOption::create(['name' => 'Cash']);

        for ($i = 1; $i <= 30; $i++) {
            $this->deal($option, 'Customer '.$i, 1000);
        }

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name')
            ->call('refreshPreview');

        // 25 rows on screen, but the subtotal is all 30.
        $this->assertCount(25, $component->get('reportData'));
        $this->assertSame(30, $component->get('previewGroups')[0]['count']);
        $this->assertEquals(30000, $component->get('previewGroups')[0]['aggregates']['contract_amount']);
    }

    public function test_a_filter_narrows_the_subtotals_too(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);
        $this->deal($cash, 'Paid', 10000);
        $this->deal($loan, 'Financed', 30000);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name')
            ->set('filterField', 'customer_finances.contract_amount')
            ->set('filterOperator', '>')
            ->set('filterValue', '20000')
            ->call('addFilter');

        $groups = $component->get('previewGroups');

        $this->assertCount(1, $groups);
        $this->assertSame('GoodLeap Financing', $groups[0]['value']);
        $this->assertEquals(30000, $component->get('previewTotals')['aggregates']['contract_amount']);
    }

    public function test_the_grouping_is_saved_with_the_report(): void
    {
        $user = $this->user();
        FinanceOption::create(['name' => 'Cash']);

        Livewire::actingAs($user)
            ->test(DynamicReportBuilder::class)
            ->set('reportName', 'By plan')
            ->set('selectedFields', ['customers.first_name'])
            ->set('groupBy', 'finance_options.name')
            ->call('saveReport');

        $this->assertSame('finance_options.name', SavedReport::where('user_id', $user->id)->value('group_by'));
    }

    public function test_a_group_field_the_user_may_not_use_is_ignored(): void
    {
        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name'])
            ->set('groupBy', 'customer_finances.holdback_amount')
            ->call('refreshPreview');

        $this->assertSame([], $component->get('previewGroups'));
    }
}
