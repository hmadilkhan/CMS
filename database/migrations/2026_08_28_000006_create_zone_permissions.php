<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Zones module's gate. Super Admin passes every gate already
 * (AuthServiceProvider's Gate::before), so only the two roles that need it
 * explicitly are wired up here.
 *
 * The Funding Manager also gets the project-page permissions it needs to work a
 * project it opened from a zone. "Project Move" is deliberately NOT among them:
 * a Funding Manager moves zones, never departments.
 */
return new class extends Migration
{
    /** Permissions the Funding Manager needs on top of the Zones board. */
    private const FUNDING_MANAGER_PERMISSIONS = [
        'View Zones',
        'View Project',
        'View Task',
        'View Customer',
        'Notes Section',
        'Files Section',
        'File Delete',
        'Project History',
        'Project Interaction',
        'Department Logs',
        'Department Tools',
        'View Tickets',
        'View Adder Details',
        'View Financial Details',
        'Invoice Details',
        'Account Transactions View',
    ];

    public function up(): void
    {
        $now = now();

        $permissionId = DB::table('permissions')
            ->where('name', 'View Zones')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'View Zones',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->grant('Funding Manager', self::FUNDING_MANAGER_PERMISSIONS);
        $this->grant('Admin', ['View Zones']);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'View Zones')->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Attach the named permissions to a role, skipping whatever it already has. */
    private function grant(string $roleName, array $permissionNames): void
    {
        $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
};
