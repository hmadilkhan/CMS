<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The builder's middle pane is the report itself, run small. It has to follow
 * every change - a column added, removed or reordered, a filter applied - or it
 * is worse than no preview at all.
 */
class ReportBuilderPreviewTest extends TestCase
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

    private function customers(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Customer::create(['first_name' => 'Customer '.$i, 'last_name' => 'Doe']);
        }
    }

    public function test_picking_a_field_runs_the_preview(): void
    {
        $this->customers(3);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', [])
            ->call('refreshPreview')
            ->assertSet('showResults', false)
            ->call('toggleField', 'customers.first_name');

        $this->assertTrue($component->get('showResults'));
        $this->assertCount(3, $component->get('reportData'));
        $this->assertSame(3, $component->get('previewCount'));
    }

    public function test_the_preview_is_capped_but_reports_the_real_count(): void
    {
        $this->customers(30);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name'])
            ->call('refreshPreview');

        $this->assertCount(25, $component->get('reportData'));
        $this->assertSame(30, $component->get('previewCount'));
        $this->assertTrue($component->instance()->getPreviewTruncatedProperty());
    }

    public function test_removing_the_last_column_empties_the_preview(): void
    {
        $this->customers(2);

        Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name'])
            ->call('refreshPreview')
            ->assertSet('showResults', true)
            ->call('removeField', 0)
            ->assertSet('showResults', false)
            ->assertSet('previewCount', 0);
    }

    public function test_columns_can_be_reordered(): void
    {
        $this->customers(1);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name', 'customers.last_name'])
            ->call('moveFieldDown', 0);

        $this->assertSame(['customers.last_name', 'customers.first_name'], $component->get('selectedFields'));

        // the preview follows the new order
        $columns = array_column($component->get('reportColumns'), 'field');
        $this->assertSame(['last_name', 'first_name'], $columns);

        $component->call('moveFieldUp', 1);
        $this->assertSame(['customers.first_name', 'customers.last_name'], $component->get('selectedFields'));
    }

    public function test_the_field_search_narrows_the_list_to_matching_groups(): void
    {
        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('fieldSearch', 'third party');

        $groups = $component->instance()->getFieldGroupsProperty();

        $this->assertSame(['Finance'], array_keys($groups));
        $this->assertSame(['customer_finances.third_party_credit'], array_keys($groups['Finance']));
    }

    public function test_a_field_the_user_may_not_report_on_is_not_offered(): void
    {
        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('fieldSearch', 'holdback');

        $this->assertSame([], $component->instance()->getFieldGroupsProperty());
    }

    public function test_adding_a_filter_narrows_the_preview(): void
    {
        Customer::create(['first_name' => 'Keep', 'last_name' => 'Doe']);
        Customer::create(['first_name' => 'Drop', 'last_name' => 'Doe']);

        $component = Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', ['customers.first_name'])
            ->call('refreshPreview')
            ->set('filterField', 'customers.first_name')
            ->set('filterOperator', '=')
            ->set('filterValue', 'Keep')
            ->call('addFilter');

        $this->assertSame(1, $component->get('previewCount'));
        $this->assertSame('Keep', $component->get('reportData')->first()->first_name);
    }
}
