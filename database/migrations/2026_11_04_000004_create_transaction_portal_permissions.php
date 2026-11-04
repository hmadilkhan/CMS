<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Transaction Portal's three gates:
 *
 *  - View Transaction Portal            the page itself
 *  - Transaction Portal Expected AR     the Expected Accounts Receivable section
 *  - Transaction Portal Other Sections  Actual AR, Commission, Labor and Material
 *
 * A section's permission is also what lets a user close its rows. The Funding
 * Manager works the Expected AR only; Admin gets everything. Super Admin passes
 * every gate already (AuthServiceProvider's Gate::before).
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'View Transaction Portal',
        'Transaction Portal Expected AR',
        'Transaction Portal Other Sections',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name) {
            $exists = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->grant('Funding Manager', ['View Transaction Portal', 'Transaction Portal Expected AR']);
        $this->grant('Admin', self::PERMISSIONS);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

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
