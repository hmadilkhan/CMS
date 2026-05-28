<?php

namespace App\Services;

use App\Models\User;

class AiAccessPolicyService
{
    public function __construct(private readonly AiPermissionService $permissionService)
    {
    }

    public function roleFor(User $user): string
    {
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->first() ?? 'User';
        }

        return 'User';
    }

    public function canAccessTable(User $user, string $table): bool
    {
        return $this->permissionService->canAccessTable($user, $table);
    }

    public function canAccessColumn(User $user, string $table, string $column): bool
    {
        return $this->permissionService->canAccessColumn($user, $table, $column);
    }

    public function canAccessFinance(User $user): bool
    {
        return $this->permissionService->canAccessFinance($user);
    }

    public function canAccessProfitability(User $user): bool
    {
        return $this->permissionService->canAccessProfitability($user);
    }

    public function applyAccessScope($query, User $user, string $table)
    {
        return $this->permissionService->applyAccessScope($query, $user, $table);
    }
}
