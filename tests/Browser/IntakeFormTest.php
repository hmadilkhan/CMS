<?php

namespace Tests\Browser;

use App\Models\Department;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\ModuleType;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class IntakeFormTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function setupSalesPerson()
    {
        UserType::create(['name' => 'Sales Partner']);
        $salesPartner = SalesPartner::create(['name' => 'Browser Test Partner']);
        
        $user = User::factory()->create([
            'user_type_id' => 3,
            'sales_partner_id' => $salesPartner->id,
        ]);
        Role::create(['name' => 'Sales Person']);
        $user->assignRole('Sales Person');

        return compact('user', 'salesPartner');
    }

    private function setupIntakeData()
    {
        $dealReview = Department::create(['id' => 1, 'name' => 'Deal Review']);
        SubDepartment::create([
            'id' => 1,
            'department_id' => $dealReview->id,
            'name' => 'New Deals',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        Role::create(['name' => 'Employee']);
        $employeeUser->assignRole('Employee');

        $employee = Employee::create([
            'id' => 1,
            'name' => 'Test Employee',
            'code' => 'EMP-001',
            'email' => 'employee@test.com',
            'phone' => '555-100-1000',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($dealReview->id);

        $financeOption = FinanceOption::create([
            'id' => 1,
            'name' => 'Cash',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
        ]);

        $inverter = InverterType::create(['name' => 'Browser Inverter']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
            'internal_base_cost' => 800,
            'internal_labor_cost' => 200,
        ]);

        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Browser Module',
            'value' => 400,
            'amount' => 120,
            'internal_module_cost' => 80,
        ]);

        return compact('financeOption', 'inverter', 'module');
    }

    public function test_sales_person_can_submit_complete_intake_form()
    {
        $sales = $this->setupSalesPerson();
        $data = $this->setupIntakeData();

        $this->browse(function (Browser $browser) use ($sales, $data) {
            $browser->loginAs($sales['user'])
                    ->visit('/intake-form/create')
                    ->pause(1000)
                    
                    // Customer Information
                    ->type('first_name', 'Browser')
                    ->type('last_name', 'TestCustomer')
                    ->type('street', '123 Test Street')
                    ->type('city', 'Phoenix')
                    ->type('state', 'AZ')
                    ->type('zipcode', '85001')
                    ->type('phone', '555-200-2000')
                    ->type('email', 'browser.test@example.com')
                    
                    // Project Details
                    ->type('panel_qty', '12')
                    ->type('sold_date', now()->format('Y-m-d'))
                    ->select('sales_partner_id', $sales['salesPartner']->id)
                    ->select('sales_partner_user_id', $sales['user']->id)
                    ->select('inverter_type_id', $data['inverter']->id)
                    ->select('module_type_id', $data['module']->id)
                    ->type('inverter_qty', '1')
                    ->type('module_qty', '4800')
                    
                    // Financial Details
                    ->select('finance_option_id', $data['financeOption']->id)
                    ->type('contract_amount', '30000')
                    ->type('redline_costs', '22000')
                    ->type('commission', '2000')
                    ->type('dealer_fee', '0')
                    ->type('dealer_fee_amount', '0')
                    ->type('adders_amount', '0')
                    ->type('sold_production_value', '6000')
                    
                    // Notes
                    ->type('notes', 'Browser test intake form submission')
                    
                    ->press('Save')
                    ->pause(3000)
                    ->assertPathIs('/intake-form')
                    ->assertSee('Browser');
        });
    }

    public function test_intake_form_validation_prevents_empty_submission()
    {
        $sales = $this->setupSalesPerson();
        $this->setupIntakeData();

        $this->browse(function (Browser $browser) use ($sales) {
            $browser->loginAs($sales['user'])
                    ->visit('/intake-form/create')
                    ->pause(1000)
                    ->press('Save')
                    ->pause(1000)
                    ->assertPresent('.error, .invalid-feedback, .alert-danger');
        });
    }

    public function test_intake_form_with_schedule_survey_option()
    {
        $sales = $this->setupSalesPerson();
        $data = $this->setupIntakeData();
        
        Department::create(['id' => 2, 'name' => 'Site Survey']);
        SubDepartment::create([
            'id' => 3,
            'department_id' => 2,
            'name' => 'Site Survey',
        ]);

        $this->browse(function (Browser $browser) use ($sales, $data) {
            $browser->loginAs($sales['user'])
                    ->visit('/intake-form/create')
                    ->pause(1000)
                    
                    ->type('first_name', 'Survey')
                    ->type('last_name', 'Customer')
                    ->type('street', '456 Survey Ave')
                    ->type('city', 'Tempe')
                    ->type('state', 'AZ')
                    ->type('zipcode', '85281')
                    ->type('phone', '555-300-3000')
                    ->type('email', 'survey.customer@example.com')
                    ->type('panel_qty', '10')
                    ->type('sold_date', now()->format('Y-m-d'))
                    ->select('sales_partner_id', $sales['salesPartner']->id)
                    ->select('sales_partner_user_id', $sales['user']->id)
                    ->select('inverter_type_id', $data['inverter']->id)
                    ->select('module_type_id', $data['module']->id)
                    ->type('inverter_qty', '1')
                    ->type('module_qty', '4000')
                    ->select('finance_option_id', $data['financeOption']->id)
                    ->type('contract_amount', '25000')
                    ->type('redline_costs', '18000')
                    ->type('commission', '1500')
                    ->type('dealer_fee', '0')
                    
                    // Schedule Survey
                    ->check('schedule_survey')
                    ->type('utility_company', 'APS')
                    ->type('ntp_approval_date', '2026-05-06')
                    ->select('hoa', 'yes')
                    ->type('hoa_phone_number', '555-400-4000')
                    
                    ->press('Save')
                    ->pause(3000);
        });
    }
}
