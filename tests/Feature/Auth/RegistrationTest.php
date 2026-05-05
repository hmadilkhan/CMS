<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_screen_redirects_to_login(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_render_crm_user_registration_screen(): void
    {
        UserType::create(['name' => 'Admin']);
        $admin = User::factory()->create();
        Role::firstOrCreate(['name' => 'Super Admin']);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get(route('get.register'));

        $response->assertOk();
    }

    public function test_super_admin_can_create_a_crm_user(): void
    {
        UserType::create(['name' => 'Admin']);
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Employee']);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->post(route('store.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'password',
            'password_confirmation' => 'password',
            'user_type_id' => 1,
            'role' => [$role->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'user_type_id' => 1,
        ]);

        $this->assertTrue(User::where('username', 'testuser')->first()->hasRole('Employee'));
    }
}
