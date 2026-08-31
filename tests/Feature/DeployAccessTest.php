<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeployAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['user_type_id' => 1]);
        $user->assignRole($role);

        return $user;
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/admin/deploy')->assertRedirect('/login');
    }

    public function test_a_signed_in_non_super_admin_cannot_reach_the_deploy_page(): void
    {
        $user = $this->userWithRole('Employee');

        $this->actingAs($user)->get('/admin/deploy')->assertForbidden();
    }

    /**
     * The POST is the dangerous one — it deploys or rolls back production — so
     * it is asserted separately. The role middleware rejects it before the
     * controller runs, which is also why this test never fires a real script.
     */
    public function test_a_signed_in_non_super_admin_cannot_trigger_a_deploy(): void
    {
        $user = $this->userWithRole('Employee');

        $this->actingAs($user)->post('/admin/deploy', ['action' => 'deploy'])->assertForbidden();
    }

    public function test_a_super_admin_can_open_the_deploy_page(): void
    {
        $user = $this->userWithRole('Super Admin');

        $this->actingAs($user)->get('/admin/deploy')->assertOk();
    }
}
