<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FormValidationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_customer_form_requires_valid_email()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/customers/create')
                    ->type('name', 'Test User')
                    ->type('email', 'invalid-email')
                    ->press('Save')
                    ->waitFor('.error, .invalid-feedback', 3)
                    ->assertSee('valid email');
        });
    }

    public function test_project_form_requires_customer()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/projects/create')
                    ->type('name', 'Test Project')
                    ->press('Save')
                    ->waitFor('.error, .invalid-feedback', 3)
                    ->assertSee('required');
        });
    }

    public function test_employee_form_requires_all_fields()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/employees/create')
                    ->press('Save')
                    ->waitFor('.error, .invalid-feedback', 3)
                    ->assertSee('required');
        });
    }
}
