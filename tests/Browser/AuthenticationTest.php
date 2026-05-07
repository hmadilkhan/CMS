<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_login_with_valid_credentials()
    {
        UserType::create(['name' => 'Admin']);
        
        // YAHA APNA REAL EMAIL AUR PASSWORD DALO
        $user = User::factory()->create([
            'email' => 'admin@example.com',  // ← YAHA APNA EMAIL DALO
            'password' => bcrypt('admin123'), // ← YAHA APNA PASSWORD DALO
            'user_type_id' => 1
        ]);
        Role::create(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'admin@example.com')     // ← YAHA BHI SAME EMAIL
                    ->type('password', 'admin123')           // ← YAHA BHI SAME PASSWORD
                    ->press('Log in')
                    ->pause(2000)
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    public function test_invalid_credentials_show_error()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'invalid@example.com')
                    ->type('password', 'wrongpassword')
                    ->press('Log in')
                    ->pause(1000)
                    ->assertPresent('.alert, .error, [role="alert"], .invalid-feedback');
        });
    }

    public function test_user_can_logout()
    {
        UserType::create(['name' => 'Admin']);
        $user = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->pause(1000)
                    ->clickLink('Logout')
                    ->pause(1000)
                    ->assertPathIs('/login');
        });
    }

    public function test_guest_cannot_access_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                    ->pause(1000)
                    ->assertPathIs('/login');
        });
    }
}
