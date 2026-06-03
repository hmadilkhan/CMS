<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the row-level access a user has to PROJECT data.
 *
 * Mirrors App\Services\ProjectService::projectQuery() so the AI assistant applies
 * exactly the same scoping the rest of the CRM uses, for BOTH engines:
 *   - the structured query builder calls applyProjectScope() to add lazy
 *     subquery predicates to its query (no DB round-trips at build time);
 *   - Text-to-SQL calls projectScopeSql()/allowedProjectIds() to splice the
 *     user's allowed project IDs inline (so the generated SQL never references
 *     extra tables that would need their own permission checks).
 */
class AiRowScopeService
{
    /**
     * Statuses that mark a task (and therefore a project) as actively assigned.
     */
    private const ACTIVE_TASK_STATUSES = ['In-Progress', 'Hold', 'Cancelled'];

    public function __construct(private readonly AiPermissionService $aiPermissionService)
    {
    }

    /**
     * True when the user sees CRM rows without any row-level scoping
     * (Super Admin, Admin, or any finance-capable role).
     */
    public function hasUnscopedAccess(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin'])
            || $this->aiPermissionService->canAccessFinance($user);
    }

    /**
     * Apply the user's project row-scope to a query whose base (or joined) table
     * is `projects`, using lazy subqueries. Mirrors ProjectService::projectQuery.
     * No-op for unscoped users; deny-all for scoped roles with no defined rule.
     */
    public function applyProjectScope(Builder $query, User $user): void
    {
        if ($this->hasUnscopedAccess($user)) {
            return;
        }

        $userId = (int) $user->id;

        switch ($user->getRoleNames()->first()) {
            case 'Sales Person':
                // Project::customer() uses withTrashed(), so customers are not
                // filtered by deleted_at here (matches ProjectService).
                $query->whereIn('projects.customer_id', DB::table('customers')
                    ->where('sales_partner_id', $userId)
                    ->select('id'));
                break;

            case 'Sales Manager':
                $query->whereIn('projects.customer_id', DB::table('customers')
                    ->where('sales_partner_id', $user->sales_partner_id)
                    ->select('id'));
                break;

            case 'Manager':
                $query->whereIn('projects.department_id', DB::table('employee_departments')
                    ->whereIn('employee_id', DB::table('employees')
                        ->whereNull('deleted_at')
                        ->where('user_id', $userId)
                        ->select('id'))
                    ->select('department_id'));
                break;

            case 'Employee':
                $latestActiveTaskIds = DB::table('tasks')
                    ->whereNull('deleted_at')
                    ->whereIn('status', self::ACTIVE_TASK_STATUSES)
                    ->groupBy('project_id')
                    ->selectRaw('MAX(id)');

                $query->whereIn('projects.id', DB::table('tasks')
                    ->whereNull('deleted_at')
                    ->whereIn('id', $latestActiveTaskIds)
                    ->whereIn('employee_id', DB::table('employees')
                        ->whereNull('deleted_at')
                        ->where('user_id', $userId)
                        ->select('id'))
                    ->select('project_id'));
                break;

            case 'Sub-Contractor User':
                // ProjectService does not scope this role; the CRM (and the prior
                // structured builder) limit it to its own sub-contract work.
                $query->where('projects.sub_contractor_user_id', $userId);
                break;

            case 'Sub-Contractor Manager':
                $query->whereIn('projects.customer_id', DB::table('customers')
                    ->where('sub_contractor_id', $user->sales_partner_id)
                    ->select('id'));
                break;

            default:
                // Scoped role with no defined project rule → deny all (fail safe).
                $query->whereRaw('1 = 0');
        }
    }

    /**
     * The project IDs a user may access, mirroring ProjectService::projectQuery.
     *
     * Returns null when the user is unscoped (no restriction needed); an empty
     * array when the user is scoped but may see no projects.
     *
     * @return array<int>|null
     */
    public function allowedProjectIds(User $user): ?array
    {
        if ($this->hasUnscopedAccess($user)) {
            return null;
        }

        $query = DB::table('projects')->whereNull('deleted_at');
        $this->applyProjectScope($query, $user);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * SQL predicate limiting the `projects` table to the user's allowed rows.
     *
     * Returns null when the user is unscoped. Uses only `projects.id` and integer
     * literals, so it is safe to splice into an AI-generated query without
     * introducing new tables or permission checks.
     */
    public function projectScopeSql(User $user): ?string
    {
        $ids = $this->allowedProjectIds($user);

        if ($ids === null) {
            return null;
        }

        if ($ids === []) {
            return 'projects.id in (0)';
        }

        return 'projects.id in (' . implode(',', $ids) . ')';
    }
}
