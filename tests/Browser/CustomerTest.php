<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UserType;
use App\Models\InverterType;
use App\Models\ModuleType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class CustomerTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function setupUser()
    {
        UserType::create(['name' => 'Admin']);
        $user = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');
        return $user;
    }

    private function setupBasicData()
    {
        $salesPartner = SalesPartner::create(['name' => 'Test Sales Partner']);
        $inverter = InverterType::create(['name' => 'Test Inverter']);
        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Test Module',
            'value' => 400,
            'amount' => 120,
            'internal_module_cost' => 80,
        ]);
        return compact('salesPartner', 'inverter', 'module');
    }

    public function test_can_view_customers_list()
    {
        $user = $this->setupUser();
        Customer::factory()->count(3)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/customers')
                    ->pause(1000)
                    ->assertSee('Customers');
        });
    }

    public function test_can_create_customer_with_full_form()
    {
        $user = $this->setupUser();
        $data = $this->setupBasicData();

        $this->browse(function (Browser $browser) use ($user, $data) {
            $browser->loginAs($user)
                    ->visit('/customers/create')
                    ->pause(1000)
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->type('street', '123 Solar Street')
                    ->type('city', 'Phoenix')
                    ->type('state', 'AZ')
                    ->type('zipcode', '85001')
                    ->type('phone', '555-100-1000')
                    ->type('email', 'john.doe@example.com')
                    ->type('panel_qty', '10')
                    ->type('sold_date', now()->format('Y-m-d'))
                    ->select('sales_partner_id', $data['salesPartner']->id)
                    ->select('inverter_type_id', $data['inverter']->id)
                    ->select('module_type_id', $data['module']->id)
                    ->type('inverter_qty', '1')
                    ->type('module_qty', '4000')
                    ->type('contract_amount', '25000')
                    ->type('redline_costs', '18000')
                    ->type('commission', '1500')
                    ->type('dealer_fee', '0')
                    ->press('Save')
                    ->pause(3000)
                    ->assertPathIs('/customers')
                    ->assertSee('John');
        });
    }

    public function test_can_edit_customer()
    {
        $user = $this->setupUser();
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]);

        $this->browse(function (Browser $browser) use ($user, $customer) {
            $browser->loginAs($user)
                    ->visit("/customers/{$customer->id}/edit")
                    ->pause(1000)
                    ->clear('first_name')
                    ->type('first_name', 'Janet')
                    ->press('Update')
                    ->pause(2000)
                    ->assertPathIs('/customers')
                    ->assertSee('Janet');
        });
    }

    public function test_customer_form_validation_shows_errors()
    {
        $user = $this->setupUser();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/customers/create')
                    ->pause(1000)
                    ->press('Save')
                    ->pause(1000)
                    ->assertPresent('.error, .invalid-feedback, .alert-danger');
        });
    }

    public function test_can_search_customers()
    {
        $user = $this->setupUser();
        Customer::factory()->create(['first_name' => 'SearchTest', 'last_name' => 'Customer']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/customers')
                    ->pause(1000)
                    ->type('input[type="search"], input[name="search"]', 'SearchTest')
                    ->pause(1000)
                    ->assertSee('SearchTest');
        });
    }
}
