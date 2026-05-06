<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_super_admin_can_create_update_and_delete_roles_and_permissions(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('save.role'), [
            'name' => 'QA Manager',
        ])->assertRedirect(route('role'));

        $role = Role::where('name', 'QA Manager')->first();
        $this->assertNotNull($role);

        $this->actingAs($admin)->post(route('update.role'), [
            'id' => $role->id,
            'name' => 'QA Manager Updated',
        ])->assertRedirect(route('role'));

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'QA Manager Updated',
        ]);

        $this->actingAs($admin)->post(route('permission.store'), [
            'name' => 'QA Permission',
        ])->assertRedirect(route('permission'));

        $permission = Permission::where('name', 'QA Permission')->first();
        $this->assertNotNull($permission);

        $this->actingAs($admin)->post(route('update.permission'), [
            'id' => $permission->id,
            'name' => 'QA Permission Updated',
        ])->assertRedirect(route('permission'));

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'QA Permission Updated',
        ]);

        $this->actingAs($admin)->post(route('permission.delete'), [
            'id' => $permission->id,
        ])
            ->assertOk()
            ->assertJson(['status' => 200]);

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);

        $this->actingAs($admin)->post(route('delete.role'), [
            'id' => $role->id,
        ])
            ->assertOk()
            ->assertJson(['status' => 200]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_super_admin_can_assign_and_clear_role_and_user_permissions(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'QA Permission Role']);
        $permissionA = Permission::create(['name' => 'QA View Projects']);
        $permissionB = Permission::create(['name' => 'QA Edit Projects']);

        $this->actingAs($admin)->post(route('store.permission'), [
            'role' => $role->id,
            'permission' => [$permissionA->name, $permissionB->name],
        ])->assertRedirect(route('role.permission'));

        $this->assertTrue($role->fresh()->hasPermissionTo($permissionA));
        $this->assertTrue($role->fresh()->hasPermissionTo($permissionB));

        $this->actingAs($admin)->post(route('delete.role.permission'), [
            'id' => $role->id,
        ])
            ->assertOk()
            ->assertJson(['status' => 200]);

        $this->assertSame([], $role->fresh()->permissions->pluck('name')->all());

        $targetUser = User::factory()->create(['user_type_id' => 1]);
        $this->actingAs($admin)->post(route('store.user.permission'), [
            'user' => $targetUser->id,
            'permission' => [$permissionA->name],
        ])->assertRedirect(route('user.permission'));

        $this->assertTrue($targetUser->fresh()->hasPermissionTo($permissionA));

        $this->actingAs($admin)->post(route('delete.user.permission'), [
            'id' => $targetUser->id,
        ])
            ->assertOk()
            ->assertJson(['status' => 200]);

        $this->assertSame([], $targetUser->fresh()->permissions->pluck('name')->all());
    }

    public function test_employee_lifecycle_creates_user_roles_departments_updates_lookup_and_soft_deletes(): void
    {
        $admin = $this->superAdmin();
        UserType::firstOrCreate(['name' => 'Employee']);
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $departmentA = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $departmentB = Department::create(['id' => 2, 'name' => 'Engineering']);

        $createResponse = $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Admin Managed Employee',
            'code' => 'EMP-ADMIN-001',
            'joined_date' => now()->toDateString(),
            'username' => 'admin-managed-employee',
            'type' => UserType::where('name', 'Employee')->value('id'),
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'overwrite_base_price' => 10,
            'overwrite_panel_price' => 2,
            'email' => 'admin.managed.employee@example.com',
            'phone' => '555-111-2222',
            'departments' => [$departmentA->id],
            'roles' => [$employeeRole->id],
        ]);

        $createResponse
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Employee created successfully',
            ]);

        $employee = Employee::where('code', 'EMP-ADMIN-001')->first();
        $this->assertNotNull($employee);
        $this->assertSame('admin-managed-employee', $employee->user->username);
        $this->assertTrue($employee->user->hasRole('Employee'));
        $this->assertDatabaseHas('employee_departments', [
            'employee_id' => $employee->id,
            'department_id' => $departmentA->id,
        ]);

        $this->actingAs($admin)->post(route('get.employee.department'), [
            'id' => $departmentA->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonFragment(['email' => 'admin.managed.employee@example.com']);

        $this->actingAs($admin)->put(route('employees.update', $employee), [
            'name' => 'Admin Managed Employee Updated',
            'code' => 'EMP-ADMIN-002',
            'joined_date' => now()->toDateString(),
            'username' => 'admin-managed-employee',
            'type' => UserType::where('name', 'Employee')->value('id'),
            'password' => '',
            'password_confirmation' => '',
            'previous_logo' => '',
            'user_id' => $employee->user_id,
            'overwrite_base_price' => 20,
            'overwrite_panel_price' => 4,
            'email' => 'admin.managed.employee.updated@example.com',
            'phone' => '555-333-4444',
            'departments' => [$departmentB->id],
            'roles' => [$managerRole->id],
        ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'messsage' => 'Employee updated successfully',
            ]);

        $employee->refresh();
        $this->assertSame('EMP-ADMIN-002', $employee->code);
        $this->assertSame('Admin Managed Employee Updated', $employee->name);
        $this->assertSame('admin.managed.employee.updated@example.com', $employee->user->email);
        $this->assertTrue($employee->user->hasRole('Manager'));
        $this->assertFalse($employee->user->hasRole('Employee'));
        $this->assertDatabaseMissing('employee_departments', [
            'employee_id' => $employee->id,
            'department_id' => $departmentA->id,
        ]);
        $this->assertDatabaseHas('employee_departments', [
            'employee_id' => $employee->id,
            'department_id' => $departmentB->id,
        ]);

        $this->actingAs($admin)->delete(route('employees.destroy', $employee))
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Employee deleted successfully',
            ]);

        $this->assertSoftDeleted($employee);
        $this->assertSoftDeleted($employee->user);
    }
}
