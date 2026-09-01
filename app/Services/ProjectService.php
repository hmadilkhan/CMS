<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\Project;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProjectService
{
    public function projectQuery(Request $request)
    {
        $query = Project::with("customer", "customer.salespartner", "department", "subdepartment", "assignedPerson", "assignedPerson.employee","task");
        $query->withCount(['emails as viewed_emails_count' => function ($query) {
            $query->where('is_view', 1);
        }]);
        $subdepartmentsQuery = SubDepartment::with("department");
        if (auth()->user()->getRoleNames()[0] == "Sales Person") {
            $query->whereHas("customer", function ($query) {
                $query->where("sales_partner_id", auth()->user()->id);
            });
        } else if (auth()->user()->getRoleNames()[0] == "Manager") {
            $query->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
        } else if (auth()->user()->getRoleNames()[0] == "Sales Manager") {
            $query->whereHas("customer", function ($query) {
                $query->where("sales_partner_id", auth()->user()->sales_partner_id);
            });
        } else if (auth()->user()->getRoleNames()[0] == "Employee") {
            $latestActiveTaskIds = Task::selectRaw("MAX(id)")
                ->whereIn("status", ["In-Progress", "Hold", "Cancelled"])
                ->groupBy("project_id");

            $query->whereIn("id", Task::whereIn("id", $latestActiveTaskIds)
                ->whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))
                ->pluck("project_id"));
        }
        if ($request->id != "" && $request->id != "all") {
            $query->where("department_id", $request->id);
            $subdepartmentsQuery->where("department_id", $request->id);
        }
        return [
            "projects" => $query->get(),
            "subdepartments" => $subdepartmentsQuery->get(),
        ];
    }

    /**
     * Roles whose members see only part of the project list. Anything else —
     * Admin, Super Admin, Funding Manager, any custom role — sees all of it.
     */
    private const NARROWED_ROLES = [
        "Sales Manager",
        "Sales Person",
        "Sub-Contractor User",
        "Sub-Contractor Manager",
        "Manager",
        "Employee",
    ];

    /**
     * Is this one project inside what the user is already allowed to see?
     *
     * Route-model binding hands a controller whatever id the request names, so an
     * endpoint acting on a project has to ask this instead of trusting the id.
     *
     * Two rules matter here, both because a gate must never be stricter than the
     * screens it guards. A user holding several roles gets the WIDEST of them —
     * the screens read only the first role, so someone who is both an Employee
     * and a Super Admin would otherwise be locked out of their own CRM. And where
     * the projects list and the dashboard disagree on a role's rule (they are two
     * separate copies today), either one granting access is enough.
     */
    public function canAccessProject(User $user, int $projectId): bool
    {
        $roles = $user->getRoleNames();
        $narrowing = $roles->intersect(self::NARROWED_ROLES);

        // Holding even one unrestricted role is enough.
        if ($narrowing->count() !== $roles->count() || $roles->isEmpty()) {
            return Project::where("id", $projectId)->exists();
        }

        return Project::where("id", $projectId)
            ->where(function ($query) use ($narrowing, $user) {
                foreach ($narrowing as $role) {
                    $query->orWhere(function ($scoped) use ($role, $user) {
                        $this->applyRolePredicate($scoped, $role, $user);
                    });
                }
            })
            ->exists();
    }

    /**
     * The rule for one narrowed role, mirroring the projects list.
     */
    private function applyRolePredicate($query, string $role, User $user): void
    {
        switch ($role) {
            case "Sales Manager":
                $query->whereHas("customer", function ($q) use ($user) {
                    $q->where("sales_partner_id", $user->sales_partner_id);
                });
                break;

            case "Sales Person":
                // The list keys off projects.sales_partner_user_id, the dashboard
                // off the customer's sales_partner_id; either one grants access.
                $query->where("sales_partner_user_id", $user->id)
                    ->orWhereHas("customer", function ($q) use ($user) {
                        $q->where("sales_partner_id", $user->id);
                    });
                break;

            case "Sub-Contractor User":
            case "Sub-Contractor Manager":
                $query->where("sub_contractor_user_id", $user->id);
                break;

            case "Manager":
                $query->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", $user->id)->pluck("id"))->pluck("department_id"));
                break;

            case "Employee":
                $latestActiveTaskIds = Task::selectRaw("MAX(id)")
                    ->whereIn("status", ["In-Progress", "Hold", "Cancelled"])
                    ->groupBy("project_id");

                $query->whereIn("id", Task::whereIn("id", $latestActiveTaskIds)
                    ->whereIn("employee_id", Employee::where("user_id", $user->id)->pluck("id"))
                    ->pluck("project_id"));
                break;
        }
    }
}
