<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Livewire\ReportRunner;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\FinanceOption;
use App\Models\SavedReport;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * An export is the copy people keep, so it carries the whole report - every
 * row, and the subtotals it is read with - not the page or the preview that
 * happened to be on screen.
 */
class ReportExportTest extends TestCase
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

    private function deals(FinanceOption $option, int $count, float $amount): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $customer = Customer::create(['first_name' => $option->name.' '.$i, 'last_name' => 'Doe']);

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
    }

    /** @return array{0: array, 1: array} the exported rows and their columns */
    private function captureExport(callable $trigger): array
    {
        Excel::fake();

        $captured = ['rows' => [], 'columns' => []];

        Excel::shouldReceive('download')->andReturnUsing(function ($export, $filename) use (&$captured) {
            $captured['rows'] = (fn () => $this->data)->call($export);
            $captured['columns'] = (fn () => $this->columns)->call($export);

            return response('ok');
        });

        $trigger();

        return [$captured['rows'], $captured['columns']];
    }

    public function test_the_builder_exports_every_row_not_the_capped_preview(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $this->deals($cash, 40, 1000);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->call('refreshPreview');

        // the preview is capped …
        $this->assertCount(25, $component->get('reportData'));

        // … and the export is not
        [$rows] = $this->captureExport(fn () => $component->call('exportExcel'));

        $this->assertCount(40, $rows);
    }

    public function test_a_grouped_export_carries_its_subtotals_and_total(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $loan = FinanceOption::create(['name' => 'GoodLeap Financing']);
        $this->deals($cash, 2, 1000);
        $this->deals($loan, 1, 5000);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name')
            ->call('refreshPreview');

        [$rows] = $this->captureExport(fn () => $component->call('exportExcel'));

        $flat = array_map(fn ($row) => implode('|', $row), $rows);

        // heading, two rows, subtotal … then the second group … then the total
        $this->assertSame('Cash (2 records)|', $flat[0]);
        $this->assertSame('Subtotal · Cash|2,000.00', $flat[3]);
        $this->assertSame('GoodLeap Financing (1 records)|', $flat[4]);
        $this->assertSame('Subtotal · GoodLeap Financing|5,000.00', $flat[6]);
        $this->assertSame('Total · 3 records|7,000.00', end($flat));
    }

    public function test_the_runner_exports_every_page(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $this->deals($cash, 60, 1000);

        $user = $this->user();

        $report = SavedReport::create([
            'name' => 'Pipeline',
            'report_type' => 'Pipeline',
            'selected_fields' => ['customers.first_name', 'customer_finances.contract_amount'],
            'group_by' => null,
            'filters' => [],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->call('runReport');

        $this->assertCount(50, $component->get('reportData'));

        [$rows] = $this->captureExport(fn () => $component->call('exportExcel'));

        $this->assertCount(60, $rows);
    }

    public function test_a_summarised_first_column_keeps_its_figure(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $this->deals($cash, 2, 1000);

        // The only column is the one being summed, so the label has nowhere to
        // go - the figure is what matters and must survive.
        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name')
            ->call('refreshPreview');

        [$rows] = $this->captureExport(fn () => $component->call('exportExcel'));

        $flat = array_map(fn ($row) => implode('|', $row), $rows);

        $this->assertContains('2,000.00', $flat, 'the subtotal lost its figure to the label');
        $this->assertSame('2,000.00', end($flat), 'the total lost its figure to the label');
    }

    public function test_a_missing_group_value_is_its_own_group(): void
    {
        $cash = FinanceOption::create(['name' => 'Cash']);
        $blank = FinanceOption::create(['name' => '']);

        $this->deals($cash, 1, 1000);
        $this->deals($blank, 1, 2000);

        // A customer with no finance row at all: its group value is NULL, which
        // must not share a bucket with the empty-named plan.
        Customer::create(['first_name' => 'No finance', 'last_name' => 'Doe']);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customer_finances.contract_amount'])
            ->set('groupBy', 'finance_options.name')
            ->call('refreshPreview');

        $groups = $component->get('previewGroups');

        // three groups, and each holds exactly the row it counted
        $this->assertCount(3, $groups);

        foreach ($groups as $group) {
            $this->assertCount(
                $group['count'],
                $group['rows'],
                'a group printed rows it does not own'
            );
        }
    }
}
