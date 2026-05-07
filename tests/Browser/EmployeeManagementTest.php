<?php

namespace Tests\Browser;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class EmployeeManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function setupAdmin()
    {
        UserType::create(['name' => 'Admin']);
        UserType::create(['name' => 'Employee']);
        
        $user = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Employee']);
        Role::create(['name' => 'Manager']);
        $user->assignRole('Super Admin');
        
        return $user;
    }

    private function setupDepartments()
    {
        $dealReview = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $engineering = Department::create(['id' => 2, 'name' => 'Engineering']);
        
        return compact('dealReview', 'engineering');
    }

    public function test_admin_can_create_employee_with_full_details()
    {
        $admin = $this->setupAdmin();
        $depts = $this->setupDepartments();

        $this->browse(function (Browser $browser) use ($admin, $depts) {
            $browser->loginAs($admin)
                    ->visit('/employees/create')
                    ->pause(1000)
                    
                    // Employee Basic Info
                    ->type('name', 'Browser Test Employee')
                    ->type('code', 'EMP-BROWSER-001')
                    ->type('joined_date', now()->format('Y-m-d'))
                    ->type('email', 'browser.employee@test.com')
                    ->type('phone', '555-700-7000')
                    
                    // User Account
                    ->type('username', 'browser-employee')
                    ->select('type', UserType::where('name', 'Employee')->value('id'))
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'Password123!')
                    
                    // Pricing Overrides
                    ->type('overwrite_base_price', '10')
                    ->type('overwrite_panel_price', '2')
                    
                    // Department & Role
                    ->check("departments[{$depts['dealReview']->id}]")
                    ->check('roles[' . Role::where('name', 'Employee')->value('id') . ']')
                    
                    ->press('Save')
                    ->pause(3000)
                    ->assertSee('Browser Test Employee');
        });
    }

    public function test_admin_can_update_employee_details()
    {
        $admin = $this->setupAdmin();
        $depts = $this->setupDepartments();

        $employeeUser = User::factory()->create([
            'user_type_id' => 2,
            'username' => 'test-employee',
            'email' => 'test.employee@test.com'
        ]);
        $employeeUser->assignRole('Employee');

        $employee = Employee::create([
            'name' => 'Original Employee',
            'code' => 'EMP-ORIG-001',
            'email' => 'test.employee@test.com',
            'phone' => '555-800-8000',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($depts['dealReview']->id);

        $this->browse(function (Browser $browser) use ($admin, $employee, $depts) {
            $browser->loginAs($admin)
                    ->visit("/employees/{$employee->id}/edit")
                    ->pause(1000)
                    
                    ->clear('name')
                    ->type('name', 'Updated Employee Name')
                    ->clear('code')
                    ->type('code', 'EMP-UPDATED-001')
                    ->clear('phone')
                    ->type('phone', '555-900-9000')
                    
                    // Change department
                    ->uncheck("departments[{$depts['dealReview']->id}]")
                    ->check("departments[{$depts['engineering']->id}]")
                    
                    // Change role
                    ->uncheck('roles[' . Role::where('name', 'Employee')->value('id') . ']')
                    ->check('roles[' . Role::where('name', 'Manager')->value('id') . ']')
                    
                    ->press('Update')
                    ->pause(3000)
                    ->assertSee('Updated Employee Name');
        });
    }

    public function test_employee_list_shows_all_employees()
    {
        $admin = $this->setupAdmin();
        $depts = $this->setupDepartments();

        $empUser = User::factory()->create(['user_type_id' => 2]);
        $empUser->assignRole('Employee');
        
        $employee = Employee::create([
            'name' => 'List Test Employee',
            'code' => 'EMP-LIST-001',
            'email' => 'list.employee@test.com',
            'phone' => '555-111-2222',
            'user_id' => $empUser->id,
        ]);
        $employee->department()->attach($depts['dealReview']->id);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/employees')
                    ->pause(1000)
                    ->assertSee('List Test Employee')
                    ->assertSee('EMP-LIST-001');
        });
    }

    public function test_employee_form_validation_prevents_empty_submission()
    {
        $admin = $this->setupAdmin();
        $this->setupDepartments();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/employees/create')
                    ->pause(1000)
                    ->press('Save')
                    ->pause(1000)
                    ->assertPresent('.error, .invalid-feedback, .alert-danger');
        });
    }

    public function test_can_filter_employees_by_department()
    {
        $admin = $this->setupAdmin();
        $depts = $this->setupDepartments();

        $empUser1 = User::factory()->create(['user_type_id' => 2]);
        $empUser1->assignRole('Employee');
        $emp1 = Employee::create([
            'name' => 'Sales Employee',
            'code' => 'EMP-SALES-001',
            'email' => 'sales@test.com',
            'phone' => '555-111-1111',
            'user_id' => $empUser1->id,
        ]);
        $emp1->department()->attach($depts['dealReview']->id);

        $empUser2 = User::factory()->create(['user_type_id' => 2]);
        $empUser2->assignRole('Employee');
        $emp2 = Employee::create([
            'name' => 'Engineering Employee',
            'code' => 'EMP-ENG-001',
            'email' => 'eng@test.com',
            'phone' => '555-222-2222',
            'user_id' => $empUser2->id,
        ]);
        $emp2->department()->attach($depts['engineering']->id);

        $this->browse(function (Browser $browser) use ($admin, $depts) {
            $browser->loginAs($admin)
                    ->visit('/employees')
                    ->pause(1000)
                    ->select('department_filter, select[name="department"]', $depts['dealReview']->id)
                    ->pause(1000)
                    ->assertSee('Sales Employee');
        });
    }
}
