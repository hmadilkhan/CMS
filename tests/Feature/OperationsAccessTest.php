<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The operations catalogue holds the company's internal cost rates and the
 * department structure the whole pipeline runs on. The sidebar only ever showed
 * it to holders of 'User Management'; these tests hold the server to the same
 * line, because hiding a menu is not access control.
 */
class OperationsAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, bool $withUserManagement = false): User
    {
        UserType::firstOrCreate(['id' => 2], ['name' => 'Employee']);

        $user = User::factory()->create(['user_type_id' => 2]);
        $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        if ($withUserManagement) {
            $roleModel->givePermissionTo(
                Permission::firstOrCreate(['name' => 'User Management', 'guard_name' => 'web'])
            );
        }

        $user->syncRoles([$roleModel]);

        return $user->fresh();
    }

    public function test_a_signed_in_user_without_the_permission_cannot_create_a_department(): void
    {
        $employee = $this->user('Employee');

        $this->actingAs($employee)
            ->post(route('department.store'), ['name' => 'Unauthorised Dept', 'document_length' => 1])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['name' => 'Unauthorised Dept']);
    }

    public function test_a_signed_in_user_without_the_permission_cannot_create_a_sales_partner(): void
    {
        $employee = $this->user('Employee');

        $this->actingAs($employee)
            ->post(route('sales.partner.store'), ['name' => 'Unauthorised Partner'])
            ->assertForbidden();

        $this->assertDatabaseMissing('sales_partners', ['name' => 'Unauthorised Partner']);
    }

    /**
     * Internal base / labor cost rates are the company's margin, and the AI
     * assistant gates them carefully — the screen that lists them has to as well.
     */
    public function test_a_signed_in_user_without_the_permission_cannot_read_internal_cost_rates(): void
    {
        $employee = $this->user('Employee');

        $this->actingAs($employee)->get(route('view-redline-cost'))->assertForbidden();
    }

    public function test_a_user_management_holder_can_create_a_department(): void
    {
        $manager = $this->user('Manager', withUserManagement: true);

        $this->actingAs($manager)
            ->post(route('department.store'), ['name' => 'Permitted Dept', 'document_length' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['name' => 'Permitted Dept']);
    }

    public function test_a_super_admin_passes_through_without_the_explicit_permission(): void
    {
        $superAdmin = $this->user('Super Admin');

        $this->actingAs($superAdmin)
            ->post(route('department.store'), ['name' => 'Super Admin Dept', 'document_length' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['name' => 'Super Admin Dept']);
    }
}
