<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginWithExistingUserTest extends DuskTestCase
{
    /**
     * EXISTING DATABASE USER SE LOGIN KARO
     * Database mein jo user already hai usse login karo
     */
    public function test_login_with_existing_database_user()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->pause(1000)
                    
                    // YAHA APNA REAL DATABASE EMAIL DALO
                    ->type('username', 'hmadilkhan')
                    
                    // YAHA APNA REAL DATABASE PASSWORD DALO
                    ->type('password', '12345678')
                    
                    ->press('Log in')
                    ->pause(3000)
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    /**
     * YA PHIR DATABASE SE USER FETCH KARO
     */
    public function test_login_with_first_admin_user()
    {
        // Database se pehla admin user le lo
        $admin = User::where('user_type_id', 1)->first();
        
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database');
        }

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->visit('/login')
                    ->pause(1000)
                    ->type('username', $admin->username)
                    ->type('password', '12345678') // YAHA PASSWORD DALO
                    ->press('Log in')
                    ->pause(3000)
                    ->assertPathIs('/dashboard');
        });
    }

    /**
     * DIRECT LOGIN (Password verify nahi karna)
     * Ye method password ki zarurat nahi hai
     */
    public function test_direct_login_without_password()
    {
        // Database se koi bhi user le lo
        $user = User::where('user_type_id', 1)->first();
        
        if (!$user) {
            $this->markTestSkipped('No user found in database');
        }

        $this->browse(function (Browser $browser) use ($user) {
            // Direct login - password ki zarurat nahi
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->pause(2000)
                    ->assertSee('Dashboard');
        });
    }
}
