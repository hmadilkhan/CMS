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
        $subdepartmentsQuery = SubDepartment::with("department");
        if (auth()->user()->getRoleNames()[0] == "Sales Person") {
            $query->whereHas("customer", function ($query) {
                $query->where("sales_partner_id", auth()->user()->id);
            });
        } else if (auth()->user()->getRoleNames()[0] == "Manager") {
            $query->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
        } else if (auth()->user()->getRoleNames()[0] == "Employee") {
            $query->whereIn("id", Task::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->whereIn("status",["In-Progress","Hold","Cancelled"])->pluck("project_id"));
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
}