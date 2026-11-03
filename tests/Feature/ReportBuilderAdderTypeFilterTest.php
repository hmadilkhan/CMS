<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Livewire\ReportRunner;
use App\Models\AdderType;
use App\Models\Customer;
use App\Models\CustomerAdder;
use App\Models\SavedReport;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A customer has any number of adders, so the adder type filters a report
 * through an EXISTS rather than a join: a customer with two matching adders
 * must still be one row.
 */
class ReportBuilderAdderTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    private const FIELD = 'customer_adders.adder_type_id';

    private AdderType $battery;

    private AdderType $mpu;

    private AdderType $trenching;

    protected function setUp(): void
    {
        parent::setUp();

        $this->battery = AdderType::create(['name' => 'Battery']);
        $this->mpu = AdderType::create(['name' => 'MPU']);
        $this->trenching = AdderType::create(['name' => 'Trenching']);

        // Ann: two battery adders. Bob: an MPU. Cy: none. Dee: only a deleted
        // battery adder, which must not count.
        $this->customer('Ann', [$this->battery, $this->battery]);
        $this->customer('Bob', [$this->mpu]);
        $this->customer('Cy', []);
        $dee = $this->customer('Dee', [$this->battery]);
        CustomerAdder::where('customer_id', $dee->id)->delete();
    }

    private function user(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'Report Builder', 'guard_name' => 'web']));
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    /** @param  array<int, AdderType>  $types */
    private function customer(string $name, array $types): Customer
    {
        $customer = Customer::create(['first_name' => $name, 'last_name' => 'Test']);

        foreach ($types as $type) {
            CustomerAdder::create([
                'customer_id' => $customer->id,
                'adder_type_id' => $type->id,
                'adder_sub_type_id' => 1,
                'adder_unit_id' => 1,
                'amount' => 500,
            ]);
        }

        return $customer;
    }

    /** First names the builder reports for one filter. */
    private function builderNames(string $operator, array $picks = []): array
    {
        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('reportType', 'forecast')
            ->set('selectedFields', ['customers.first_name'])
            ->set('filterField', self::FIELD)
            ->set('filterOperator', $operator)
            ->set('filterValueList', array_map(fn ($type) => (string) $type->id, $picks))
            ->call('addFilter')
            ->assertHasNoErrors();

        return $component->get('reportData')->pluck('first_name')->sort()->values()->all();
    }

    public function test_the_builder_offers_adder_type_as_a_filter_but_not_a_column(): void
    {
        $component = Livewire::actingAs($this->user())->test(DynamicReportBuilder::class);

        $this->assertArrayHasKey(self::FIELD, $component->instance()->filterFields);
        $this->assertArrayNotHasKey(self::FIELD, $component->instance()->availableFields);

        $component->set('filterField', self::FIELD);
        $this->assertSame(
            ['=', '!=', 'IS NOT NULL', 'IS NULL'],
            array_keys($component->instance()->filterOperators)
        );
        $this->assertSame(
            ['Battery', 'MPU', 'Trenching'],
            array_values($component->instance()->getDropdownOptions(self::FIELD))
        );
    }

    public function test_the_filter_field_dropdown_is_grouped_and_unnarrowed_by_the_field_search(): void
    {
        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('fieldSearch', 'zip');

        $groups = $component->instance()->filterFieldGroups;

        $this->assertSame([self::FIELD => 'Adder Type'], $groups['Adders']);
        $this->assertArrayHasKey('customer_finances.contract_amount', $groups['Finance']);
        $this->assertArrayNotHasKey('Adders', $component->instance()->fieldGroups);
    }

    public function test_the_second_group_level_leaves_out_the_first(): void
    {
        $builder = Livewire::actingAs($this->user())->test(DynamicReportBuilder::class)->instance();

        $this->assertArrayHasKey('customers.city', $builder->groupByFieldGroups()['Customer']);
        $this->assertArrayNotHasKey('customers.city', $builder->groupByFieldGroups('customers.city')['Customer']);
        // A filter-only field cannot group a report.
        $this->assertArrayNotHasKey('Adders', $builder->groupByFieldGroups());
    }

    public function test_has_a_type_lists_each_customer_once(): void
    {
        $this->assertSame(['Ann'], $this->builderNames('=', [$this->battery]));
    }

    public function test_several_picked_types_match_any_of_them(): void
    {
        $this->assertSame(['Ann', 'Bob'], $this->builderNames('=', [$this->battery, $this->mpu]));
    }

    public function test_does_not_have_a_type_keeps_customers_without_adders(): void
    {
        $this->assertSame(['Bob', 'Cy', 'Dee'], $this->builderNames('!=', [$this->battery]));
    }

    public function test_has_any_adder_and_has_no_adders(): void
    {
        $this->assertSame(['Ann', 'Bob'], $this->builderNames('IS NOT NULL'));
        $this->assertSame(['Cy', 'Dee'], $this->builderNames('IS NULL'));
    }

    public function test_a_comparison_operator_is_refused_for_the_adder_type(): void
    {
        Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('filterField', self::FIELD)
            ->set('filterOperator', '>')
            ->set('filterValue', '1')
            ->call('addFilter')
            ->assertHasErrors('filterOperator')
            ->assertSet('filters', []);
    }

    public function test_a_saved_report_filters_by_adder_type_when_run(): void
    {
        $owner = $this->user();

        $report = SavedReport::create([
            'name' => 'Adders',
            'report_type' => 'Adders',
            'selected_fields' => ['customers.first_name'],
            'filters' => [[
                'field' => self::FIELD,
                'operator' => '=',
                'value' => (string) $this->mpu->id,
                'field_name' => 'Adder Type',
                'value_label' => 'MPU',
            ]],
            'calculated_fields' => [],
            'query' => '{}',
            'user_id' => $owner->id,
        ]);

        $component = Livewire::actingAs($owner)
            ->test(ReportRunner::class)
            ->set('selectedReportId', $report->id)
            ->set('filterValues.0', [(string) $this->trenching->id, (string) $this->mpu->id])
            ->call('runReport')
            ->assertSee('Has');

        $this->assertSame(['Bob'], collect($component->get('reportData'))->pluck('first_name')->all());
    }
}
