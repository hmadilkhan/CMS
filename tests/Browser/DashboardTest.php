<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_dashboard_loads_successfully()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->assertSee('Dashboard')
                    ->assertVisible('.dashboard-content, .main-content');
        });
    }

    public function test_navigation_menu_works()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->clickLink('Customers')
                    ->waitForLocation('/customers')
                    ->assertPathIs('/customers')
                    ->clickLink('Projects')
                    ->waitForLocation('/projects')
                    ->assertPathIs('/projects')
                    ->clickLink('Employees')
                    ->waitForLocation('/employees')
                    ->assertPathIs('/employees');
        });
    }

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin-dashboard')
                    ->assertSee('Admin Dashboard');
        });
    }

    public function test_regular_user_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin-dashboard')
                    ->assertPathIsNot('/admin-dashboard');
        });
    }
}
