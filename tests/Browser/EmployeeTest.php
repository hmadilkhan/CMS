<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EmployeeTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_can_view_employees_list()
    {
        $user = User::factory()->create();
        Employee::factory()->count(3)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/employees')
                    ->assertSee('Employees');
        });
    }

    public function test_can_create_employee()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/employees/create')
                    ->type('name', 'Jane Smith')
                    ->type('email', 'jane@example.com')
                    ->type('phone', '9876543210')
                    ->select('department', 'Sales')
                    ->press('Save')
                    ->waitForLocation('/employees')
                    ->assertSee('Jane Smith');
        });
    }

    public function test_can_filter_employees_by_department()
    {
        $user = User::factory()->create();
        Employee::factory()->create(['department' => 'Sales']);
        Employee::factory()->create(['department' => 'Engineering']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/employees')
                    ->select('department_filter', 'Sales')
                    ->pause(500)
                    ->assertSee('Sales');
        });
    }
}
