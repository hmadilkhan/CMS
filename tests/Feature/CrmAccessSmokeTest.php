<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmAccessSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_protected_crm_pages(): void
    {
        $protectedUrls = [
            '/dashboard',
            '/admin-dashboard',
            '/employees',
            '/customers',
            '/intake-form',
            '/projects',
            '/module-types',
            '/office-costs',
            '/labor-costs',
            '/tools',
            '/tickets',
            '/profile',
        ];

        foreach ($protectedUrls as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_super_admin_can_open_core_crm_entry_points(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        foreach (['/admin-dashboard', '/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }
}
