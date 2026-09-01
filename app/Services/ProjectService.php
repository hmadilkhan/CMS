<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\Project;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $allowed = Project::where("id", $projectId)
            ->where(function ($query) use ($narrowing, $user) {
                foreach ($narrowing as $role) {
                    $query->orWhere(function ($scoped) use ($role, $user) {
                        $this->applyRolePredicate($scoped, $role, $user);
                    });
                }

                $this->applyDirectInvolvement($query, $user);
            })
            ->exists();

        if (! $allowed) {
            // Left behind on purpose: a denial that turns out to be legitimate is
            // a gap in the rules above, and this is how it gets found instead of
            // being reported as "the link is broken".
            Log::warning("Project access denied", [
                "user_id" => $user->id,
                "roles" => $user->getRoleNames()->all(),
                "project_id" => $projectId,
            ]);
        }

        return $allowed;
    }

    /**
     * Ways a user is tied to one project regardless of what their role's slice of
     * the list looks like.
     *
     * Five notifications deep-link to a project page — a mention in a note, an
     * assignment, a date change, an incoming email — and they reach whoever is
     * involved, not only whoever the list shows. Someone who worked the project
     * earlier, or was mentioned on it, has to be able to follow that link.
     */
    private function applyDirectInvolvement($query, User $user): void
    {
        $employeeIds = Employee::where("user_id", $user->id)->pluck("id");

        $query
            // The project points straight at them.
            ->orWhere("sales_partner_user_id", $user->id)
            ->orWhere("sub_contractor_user_id", $user->id)
            // They hold, or ever held, a task on it.
            ->orWhereExists(function ($sub) use ($employeeIds) {
                $sub->select(DB::raw(1))
                    ->from("tasks")
                    ->whereColumn("tasks.project_id", "projects.id")
                    ->whereNull("tasks.deleted_at")
                    ->whereIn("tasks.employee_id", $employeeIds);
            })
            // They were mentioned in a note on it.
            ->orWhereExists(function ($sub) use ($user, $employeeIds) {
                $sub->select(DB::raw(1))
                    ->from("notes_mentions")
                    ->whereColumn("notes_mentions.project_id", "projects.id")
                    ->where(function ($who) use ($user, $employeeIds) {
                        $who->where("notes_mentions.user_id", $user->id)
                            ->orWhereIn("notes_mentions.employee_id", $employeeIds);
                    });
            })
            // They wrote a note on it.
            ->orWhereExists(function ($sub) use ($user) {
                $sub->select(DB::raw(1))
                    ->from("department_notes")
                    ->whereColumn("department_notes.project_id", "projects.id")
                    ->where("department_notes.user_id", $user->id);
            });
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
