<?php

namespace Tests\Feature;

use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\LaborCost;
use App\Models\LoanTerm;
use App\Models\ModuleType;
use App\Models\OfficeCost;
use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_module_type_can_be_created_updated_and_deleted(): void
    {
        $admin = $this->superAdmin();
        $inverter = InverterType::create(['name' => 'Operations Inverter']);

        $createResponse = $this->actingAs($admin)->post(route('module-types.store'), [
            'name' => 'Operations Module',
            'inverter_type_id' => $inverter->id,
            'value' => 410,
            'amount' => 130,
            'internal_module_cost' => 90,
            'ptc_rating' => 385.5,
            'voc_rating' => 49.8,
            'isc_rating' => 10.6,
            'weight' => 52.7,
            'square_footage' => 23.4,
        ]);

        $createResponse->assertRedirect(route('module-types.index'));

        $module = ModuleType::where('name', 'Operations Module')->first();
        $this->assertNotNull($module);

        $updateResponse = $this->actingAs($admin)->put(route('module-types.update', $module), [
            'name' => 'Operations Module Updated',
            'inverter_type_id' => $inverter->id,
            'value' => 420,
            'amount' => 140,
            'internal_module_cost' => 95,
            'ptc_rating' => 390.5,
            'voc_rating' => 50.8,
            'isc_rating' => 11.6,
            'weight' => 53.7,
            'square_footage' => 24.4,
        ]);

        $updateResponse->assertRedirect(route('module-types.index'));
        $this->assertDatabaseHas('module_types', [
            'id' => $module->id,
            'name' => 'Operations Module Updated',
            'value' => 420,
            'amount' => 140,
            'internal_module_cost' => 95,
            'ptc_rating' => 390.5,
            'voc_rating' => 50.8,
            'isc_rating' => 11.6,
            'weight' => 53.7,
            'square_footage' => 24.4,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('module-types.destroy', $module));

        $deleteResponse
            ->assertOk()
            ->assertJson(['status' => 200]);
        $this->assertSoftDeleted($module);
    }

    public function test_office_cost_save_replaces_previous_cost(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('office-costs.store'), ['cost' => 1000])
            ->assertRedirect(route('office-costs.index'));

        $firstCost = OfficeCost::first();
        $this->assertNotNull($firstCost);
        $this->assertSame(1000.0, (float) $firstCost->cost);

        $this->actingAs($admin)->post(route('office-costs.store'), ['cost' => 1500])
            ->assertRedirect(route('office-costs.index'));

        $this->assertSoftDeleted($firstCost);
        $this->assertDatabaseHas('office_costs', [
            'cost' => 1500,
            'deleted_at' => null,
        ]);
    }

    public function test_labor_cost_save_replaces_previous_cost(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('labor-costs.store'), ['cost' => 45])
            ->assertRedirect(route('labor-costs.index'));

        $firstCost = LaborCost::first();
        $this->assertNotNull($firstCost);
        $this->assertSame(45.0, (float) $firstCost->cost);

        $this->actingAs($admin)->post(route('labor-costs.store'), ['cost' => 55])
            ->assertRedirect(route('labor-costs.index'));

        $this->assertSoftDeleted($firstCost);
        $this->assertDatabaseHas('labor_costs', [
            'cost' => 55,
            'deleted_at' => null,
        ]);
    }

    public function test_finance_option_can_be_created_updated_and_deleted_with_default_terms(): void
    {
        $admin = $this->superAdmin();

        $createResponse = $this->actingAs($admin)->post(route('finance.option.store'), [
            'name' => 'Operations Financing',
            'loan_id' => 1,
            'production_requirements' => 1,
            'positive_variance' => 10,
            'negative_variance' => 5,
            'dealer_fee' => 1,
            'pto_restriction' => 1,
            'no_of_days' => 30,
            'holdback' => 1,
            'dollar_watt_value' => 0.25,
        ]);

        $createResponse->assertRedirect(route('finance.option.types'));

        $finance = FinanceOption::where('name', 'Operations Financing')->first();
        $this->assertNotNull($finance);

        $this->assertDatabaseHas('loan_terms', [
            'finance_option_id' => $finance->id,
            'year' => '10 Years',
        ]);
        $this->assertDatabaseHas('loan_terms', [
            'finance_option_id' => $finance->id,
            'year' => '25 Years',
        ]);

        $updateResponse = $this->actingAs($admin)->post(route('finance.option.update'), [
            'id' => $finance->id,
            'name' => 'Operations Financing Updated',
            'loan_id' => 0,
            'production_requirements' => 0,
            'positive_variance' => 99,
            'negative_variance' => 99,
            'dealer_fee' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 99,
            'holdback' => 0,
            'dollar_watt_value' => 99,
        ]);

        $updateResponse->assertRedirect(route('finance.option.types'));
        $this->assertDatabaseHas('finance_options', [
            'id' => $finance->id,
            'name' => 'Operations Financing Updated',
            'loan_id' => 0,
            'production_requirements' => 0,
            'positive_variance' => 0,
            'negative_variance' => 0,
            'dealer_fee' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
        ]);

        $deleteResponse = $this->actingAs($admin)->post(route('finance.option.delete'), [
            'id' => $finance->id,
        ]);

        $deleteResponse
            ->assertOk()
            ->assertJson(['status' => 200]);
        $this->assertSoftDeleted($finance);
        $this->assertSame(0, LoanTerm::where('finance_option_id', $finance->id)->count());
    }

    public function test_sales_partner_can_be_created_updated_and_deleted(): void
    {
        $admin = $this->superAdmin();

        $createResponse = $this->actingAs($admin)->post(route('sales.partner.store'), [
            'name' => 'Operations Sales Partner',
            'email' => 'ops-partner@example.com',
            'phone' => '555-900-9000',
        ]);

        $createResponse->assertRedirect(route('sales.partner.types'));

        $partner = SalesPartner::where('name', 'Operations Sales Partner')->first();
        $this->assertNotNull($partner);

        $updateResponse = $this->actingAs($admin)->post(route('sales.partner.update'), [
            'id' => $partner->id,
            'name' => 'Operations Sales Partner Updated',
            'email' => 'ops-partner-updated@example.com',
            'phone' => '555-999-9999',
            'previous_logo' => '',
        ]);

        $updateResponse->assertRedirect(route('sales.partner.types'));
        $this->assertDatabaseHas('sales_partners', [
            'id' => $partner->id,
            'name' => 'Operations Sales Partner Updated',
            'email' => 'ops-partner-updated@example.com',
            'phone' => '555-999-9999',
        ]);

        $deleteResponse = $this->actingAs($admin)->post(route('sales.partner.delete'), [
            'id' => $partner->id,
        ]);

        $deleteResponse
            ->assertOk()
            ->assertJson(['status' => 200]);
        $this->assertSoftDeleted($partner);
    }
}
