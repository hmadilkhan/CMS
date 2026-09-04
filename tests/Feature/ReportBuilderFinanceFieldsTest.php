<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Livewire\ReportRunner;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\SavedReport;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The report builder's field list is hand-written, so it drifts from the
 * customer_finances table every time a column is added - the Prepaid PPA pair
 * (third party credit / customer portion) was missing from it. The list is also
 * the security boundary: a selected field is spliced into the query as raw SQL.
 */
class ReportBuilderFinanceFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string ...$permissions): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $user->syncRoles([$role]);

        return $user->fresh();
    }

    private function customerWithFinance(): Customer
    {
        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => 1,
            'contract_amount' => 30000,
            'redline_costs' => 20000,
            'adders' => '0',
            'commission' => 1000,
            'dealer_fee' => 10,
            'dealer_fee_amount' => 3000,
            'third_party_credit' => 7500.50,
            'customer_portion' => 22499.50,
            'holdback_amount' => 1250,
        ]);

        return $customer;
    }

    public function test_the_prepaid_ppa_fields_are_offered_by_the_builder(): void
    {
        Livewire::actingAs($this->user('Report Builder'))
            ->test(DynamicReportBuilder::class)
            ->assertSee('Third Party Credit')
            ->assertSee('Customer Portion');
    }

    public function test_a_report_returns_the_prepaid_ppa_values(): void
    {
        $this->customerWithFinance();

        $component = Livewire::actingAs($this->user('Report Builder'))
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', [
                'customers.first_name',
                'customer_finances.third_party_credit',
                'customer_finances.customer_portion',
            ])
            ->call('generateReport');

        $row = $component->get('reportData')->first();

        $this->assertEquals(7500.50, $row->third_party_credit);
        $this->assertEquals(22499.50, $row->customer_portion);
    }

    public function test_the_holdback_amount_follows_its_own_permission(): void
    {
        Livewire::actingAs($this->user('Report Builder'))
            ->test(DynamicReportBuilder::class)
            ->assertDontSee('Holdback Amount');

        Livewire::actingAs($this->user('Report Builder', 'Holdback Amount'))
            ->test(DynamicReportBuilder::class)
            ->assertSee('Holdback Amount');
    }

    public function test_a_field_the_builder_never_offered_is_not_queried(): void
    {
        $this->customerWithFinance();

        // $selectedFields is a public property, so this is what the browser can
        // send. It used to reach the database inside a DB::raw() select.
        $component = Livewire::actingAs($this->user('Report Builder'))
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', [
                'customers.first_name',
                '(select group_concat(password) from users) as leak',
            ])
            ->call('generateReport');

        $component->assertOk();

        $row = $component->get('reportData')->first();

        $this->assertFalse(property_exists($row, 'leak'));
        $this->assertSame('Jane', $row->first_name);
    }

    public function test_the_builder_needs_the_report_builder_permission(): void
    {
        $this->actingAs($this->user())->get(route('report-builder'))->assertForbidden();
        $this->actingAs($this->user())->get(route('report-runner'))->assertForbidden();

        $this->actingAs($this->user('Report Builder'))->get(route('report-builder'))->assertOk();
        $this->actingAs($this->user('Report Builder'))->get(route('report-runner'))->assertOk();
    }

    public function test_a_saved_report_can_only_be_run_by_its_owner(): void
    {
        $this->customerWithFinance();

        $owner = $this->user('Report Builder');
        $stranger = $this->user('Report Builder');

        $report = SavedReport::create([
            'name' => 'Contract amounts',
            'report_type' => 'Contract amounts',
            'selected_fields' => ['customers.first_name', 'customer_finances.contract_amount'],
            'filters' => [],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $owner->id,
        ]);

        Livewire::actingAs($stranger)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->assertSet('selectedReport', null);

        Livewire::actingAs($owner)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->call('runReport')
            ->assertSee('Contract Amount');
    }

    public function test_a_saved_report_cannot_smuggle_in_a_field(): void
    {
        $this->customerWithFinance();

        $owner = $this->user('Report Builder');

        $report = SavedReport::create([
            'name' => 'Tampered',
            'report_type' => 'Tampered',
            'selected_fields' => [
                'customers.first_name',
                '(select group_concat(password) from users) as users.leak',
            ],
            'filters' => [],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $owner->id,
        ]);

        $component = Livewire::actingAs($owner)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->call('runReport');

        $component->assertOk();

        $row = $component->get('reportData')->first();

        $this->assertFalse(property_exists($row, 'leak'));
    }
}
