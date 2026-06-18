<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AiPermissionService
{
    public function __construct(private readonly AiSchemaService $aiSchemaService)
    {
    }

    public function canAccessTable($user, $table): bool
    {
        // Security: always validate schema access and role access before query execution.
        if (! $user instanceof User || ! $this->aiSchemaService->isTableAllowed($table)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        $accessRule = $this->aiSchemaService->getAccessRule($table);

        if ($accessRule === 'finance_access') {
            return $this->canAccessFinance($user);
        }

        if ($accessRule === 'profitability_access') {
            return $this->canAccessProfitability($user);
        }

        if ($accessRule === 'invoice_details_access') {
            return method_exists($user, 'can') && $user->can('Invoice Details');
        }

        if ($accessRule === 'admin_only') {
            return false;
        }

        if ($this->canAccessFinance($user) && in_array($accessRule, ['project_access', 'customer_access'], true)) {
            return true;
        }

        if ($this->hasAnyRole($user, ['Manager', 'Sales Manager', 'Sub-Contractor Manager'])) {
            return in_array($accessRule, [
                'project_access',
                'customer_access',
                'ticket_access',
                'department_access',
                'user_access',
            ], true);
        }

        if ($this->hasAnyRole($user, ['Employee', 'Sales Person', 'Sub-Contractor User'])) {
            return in_array($table, [
                'projects',
                'tasks',
                'service_tickets',
                'service_ticket_comments',
                'project_follow_ups',
                'project_call_logs',
                'project_files',
                'project_design_details',
                'project_invoice_details',
            ], true);
        }

        return false;
    }

    public function canAccessColumn($user, $table, $column): bool
    {
        if (! $this->canAccessTable($user, $table)) {
            return false;
        }

        if (! $this->aiSchemaService->isColumnAllowed($table, $column)) {
            return false;
        }

        if ($this->aiSchemaService->isSensitiveColumn($table, $column)) {
            $accessRule = $this->aiSchemaService->getAccessRule($table);

            if ($accessRule === 'profitability_access') {
                return $this->canAccessProfitability($user);
            }

            return $this->canAccessFinance($user);
        }

        return true;
    }

    public function canAccessFinance($user): bool
    {
        return $user instanceof User && $this->hasAnyRole($user, [
            'Super Admin',
            'Admin',
            'Finance',
        ]);
    }

    public function canAccessProfitability($user): bool
    {
        return $this->canAccessFinance($user);
    }

    public function applyAccessScope($query, $user, $table)
    {
        if (! $user instanceof User || ! $query instanceof Builder) {
            return $query;
        }

        if ($this->isAdmin($user) || $this->canAccessFinance($user)) {
            return $query;
        }

        if ($this->hasAnyRole($user, ['Manager'])) {
            $departmentIds = $this->departmentIdsFor($user);

            return match ($table) {
                'projects', 'tasks', 'project_follow_ups', 'project_call_logs', 'project_files', 'project_design_details' => $query->whereIn('department_id', $departmentIds),
                'project_invoice_details' => $query->whereHas('project', fn ($projectQuery) => $projectQuery->whereIn('department_id', $departmentIds)),
                'service_tickets' => $query->whereHas('project', fn ($projectQuery) => $projectQuery->whereIn('department_id', $departmentIds)),
                'customers' => $query->whereHas('project', fn ($projectQuery) => $projectQuery->whereIn('department_id', $departmentIds)),
                default => $query,
            };
        }

        if ($this->hasAnyRole($user, ['Employee'])) {
            $employeeIds = $this->employeeIdsFor($user);

            return match ($table) {
                'projects' => $query->whereIn('id', Task::whereIn('employee_id', $employeeIds)->select('project_id')),
                'tasks', 'project_follow_ups', 'project_design_details' => $query->whereIn('employee_id', $employeeIds),
                'project_invoice_details' => $query->whereIn('project_id', Task::whereIn('employee_id', $employeeIds)->select('project_id')),
                'service_tickets' => $query->where('assigned_to', $user->id)->orWhere('user_id', $user->id),
                'service_ticket_comments' => $query->where('user_id', $user->id),
                'project_call_logs' => $query->where('user_id', $user->id),
                default => $query,
            };
        }

        if ($this->hasAnyRole($user, ['Sales Person'])) {
            return match ($table) {
                'projects' => $query->where('sales_partner_user_id', $user->id),
                'project_invoice_details' => $query->whereHas('project', fn ($projectQuery) => $projectQuery->where('sales_partner_user_id', $user->id)),
                'service_tickets' => $query->where('user_id', $user->id)->orWhere('assigned_to', $user->id),
                default => $query,
            };
        }

        if ($this->hasAnyRole($user, ['Sub-Contractor User'])) {
            return match ($table) {
                'projects' => $query->where('sub_contractor_user_id', $user->id),
                'project_invoice_details' => $query->whereHas('project', fn ($projectQuery) => $projectQuery->where('sub_contractor_user_id', $user->id)),
                'service_tickets' => $query->where('user_id', $user->id)->orWhere('assigned_to', $user->id),
                default => $query,
            };
        }

        return $query;
    }

    private function isAdmin(User $user): bool
    {
        return $this->hasAnyRole($user, ['Super Admin', 'Admin']);
    }

    private function hasAnyRole(User $user, array $roles): bool
    {
        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles);
    }

    private function employeeIdsFor(User $user)
    {
        return Employee::where('user_id', $user->id)->select('id');
    }

    private function departmentIdsFor(User $user)
    {
        return EmployeeDepartment::whereIn('employee_id', $this->employeeIdsFor($user))->select('department_id');
    }
}
