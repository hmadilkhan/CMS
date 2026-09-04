<?php

namespace Tests\Feature;

use App\Livewire\DynamicReportBuilder;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\Project;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The report query starts from customers and joins in what the selected fields
 * need. Two things it was getting wrong: it joined a table once per reason to
 * join it (and 'departments.' matches the tail of 'sub_departments.'), and it
 * ignored soft deletes on every table it joined.
 */
class ReportQueryJoinsTest extends TestCase
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

    private function customerWithProject(string $name = 'Jane'): Customer
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        SubDepartment::firstOrCreate(['id' => 1], ['name' => 'New Deals', 'department_id' => 1]);

        $customer = Customer::create(['first_name' => $name, 'last_name' => 'Doe']);

        Project::create([
            'project_name' => $name.' Project',
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
        ]);

        return $customer;
    }

    private function report(array $fields, array $filters = [])
    {
        return Livewire::actingAs($this->user())
            ->test(DynamicReportBuilder::class)
            ->set('selectedFields', $fields)
            ->set('filters', $filters)
            ->call('generateReport');
    }

    public function test_a_project_and_a_department_column_can_be_reported_together(): void
    {
        $this->customerWithProject();

        $component = $this->report([
            'customers.first_name',
            'projects.project_name',
            'departments.name',
        ]);

        $component->assertOk();
        $this->assertSame('Deal Review', $component->get('reportData')->first()->name);
    }

    public function test_a_sub_department_column_joins_the_project_once(): void
    {
        $this->customerWithProject();

        $component = $this->report([
            'customers.first_name',
            'projects.project_name',
            'sub_departments.name',
        ]);

        $component->assertOk();
        $this->assertSame('New Deals', $component->get('reportData')->first()->name);
    }

    public function test_a_deleted_finance_row_is_not_reported(): void
    {
        $customer = $this->customerWithProject();

        $old = CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => 1,
            'contract_amount' => 10000,
            'redline_costs' => 0,
            'adders' => '0',
            'commission' => 0,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
        ]);
        $old->delete();

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => 1,
            'contract_amount' => 30000,
            'redline_costs' => 0,
            'adders' => '0',
            'commission' => 0,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
        ]);

        $rows = $this->report([
            'customers.first_name',
            'customer_finances.contract_amount',
        ])->get('reportData');

        $this->assertCount(1, $rows);
        $this->assertEquals(30000, $rows->first()->contract_amount);
    }

    public function test_a_customer_without_a_project_still_appears(): void
    {
        Customer::create(['first_name' => 'Projectless', 'last_name' => 'Doe']);

        $rows = $this->report([
            'customers.first_name',
            'projects.project_name',
        ])->get('reportData');

        $this->assertCount(1, $rows);
        $this->assertNull($rows->first()->project_name);
    }

    public function test_a_filter_can_use_a_table_no_column_was_picked_from(): void
    {
        $this->customerWithProject('Jane');
        Customer::create(['first_name' => 'Nobody', 'last_name' => 'Doe']);

        $rows = $this->report(
            ['customers.first_name'],
            [[
                'field' => 'departments.name',
                'operator' => '=',
                'value' => 'Deal Review',
                'field_name' => 'Department Name',
            ]]
        )->get('reportData');

        $this->assertCount(1, $rows);
        $this->assertSame('Jane', $rows->first()->first_name);
    }
}
