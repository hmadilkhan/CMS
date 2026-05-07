<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function setupAdmin()
    {
        UserType::create(['name' => 'Admin']);
        $user = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');
        return $user;
    }

    public function test_admin_can_create_new_role()
    {
        $admin = $this->setupAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/role')
                    ->pause(1000)
                    ->type('name', 'QA Manager')
                    ->press('Save')
                    ->pause(2000)
                    ->assertSee('QA Manager');
        });
    }

    public function test_admin_can_update_role()
    {
        $admin = $this->setupAdmin();
        $role = Role::create(['name' => 'Original Role']);

        $this->browse(function (Browser $browser) use ($admin, $role) {
            $browser->loginAs($admin)
                    ->visit("/role/{$role->id}")
                    ->pause(1000)
                    ->clear('name')
                    ->type('name', 'Updated Role Name')
                    ->press('Update')
                    ->pause(2000)
                    ->assertSee('Updated Role Name');
        });
    }

    public function test_admin_can_create_permission()
    {
        $admin = $this->setupAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/permission')
                    ->pause(1000)
                    ->type('name', 'View Reports')
                    ->press('Save')
                    ->pause(2000)
                    ->assertSee('View Reports');
        });
    }

    public function test_admin_can_assign_permissions_to_role()
    {
        $admin = $this->setupAdmin();
        $role = Role::create(['name' => 'Test Role']);
        Permission::create(['name' => 'Edit Projects']);
        Permission::create(['name' => 'Delete Projects']);

        $this->browse(function (Browser $browser) use ($admin, $role) {
            $browser->loginAs($admin)
                    ->visit("/role-permission/{$role->id}")
                    ->pause(1000)
                    ->check('permission[]')
                    ->press('Save')
                    ->pause(2000)
                    ->assertSee('success');
        });
    }

    public function test_non_admin_cannot_access_role_management()
    {
        UserType::create(['name' => 'Employee']);
        $user = User::factory()->create(['user_type_id' => 2]);
        Role::create(['name' => 'Employee']);
        $user->assignRole('Employee');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/role')
                    ->pause(1000)
                    ->assertPathIsNot('/role');
        });
    }
}
