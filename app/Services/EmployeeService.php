<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\User;
use App\Traits\MediaTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeService
{
    use MediaTrait;

    public function getAllEmployeesWithDepartments()
    {
        return Employee::with("department")->get();
    }

    public function getFormCreateData()
    {
        return [
            "employee" => [],
            "roles" => Role::all(),
            "departments" => Department::all(),
        ];
    }

    public function getFormEditData(Employee $employee)
    {
        $user = User::findOrFail($employee->user_id);
        return [
            "employee" => $employee,
            "roles" => Role::all(),
            "departments" => Department::all(),
            "userRoles" => $user->getRoleNames(),
        ];
    }

    public function createEmployee($data)
    {
        DB::beginTransaction();
        $data->validated();
        try {
            $result = $this->handleFileUpload($data['file'] ?? null, 'employees/');
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'username' => $data['username'],
                'user_type_id' => 2,
                'overwrite_base_price' => $data['overwrite_base_price'],
                'overwrite_panel_price' => $data['overwrite_panel_price'],
            ]);
            foreach ($data['roles'] as $key => $role) {
                $user->assignRole($role);
            }

            $employee = Employee::create(array_merge($data->except(["file", "id", "previous_logo", "roles", "username", "password", "password_confirmation", "user_id", "departments", "overwrite_base_price", "overwrite_panel_price"]), ['user_id' => $user->id, 'image' => $result["fileName"]]));

            $this->attachDepartments($employee->id, $data['departments']);

            DB::commit();
            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateEmployee(Employee $employee, $data)
    {
        try {
            $data->validated();
            DB::beginTransaction();
            $result = $this->handleFileUpload($data['file'] ?? null, 'employees/', $data["previous_logo"]);
            $user = User::findOrFail($data["user_id"]);
            $user->update([
                'name' => $data["name"],
                'email' => $data["email"],
                'overwrite_base_price' => $data['overwrite_base_price'],
                'overwrite_panel_price' => $data['overwrite_panel_price'],
            ]);
            $user->syncRoles($data["roles"]);
            $employee->update(
                array_merge(
                    $data->except(["file", "id", "previous_logo", "roles", "username", "password", "password_confirmation", "user_id", "departments", "overwrite_base_price", "overwrite_panel_price"]),
                    [
                        "user_id" => $data["user_id"],
                        "image" => (!empty($result) ? $result["fileName"] : $data["previous_logo"]),
                    ]
                )
            );
            EmployeeDepartment::where("employee_id", $employee->id)->delete();
            $this->attachDepartments($employee->id, $data['departments']);

            DB::commit();
            return response()->json(["status" => 200, "messsage" => "Employee updated successfully"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "messsage" => $th->getMessage()]);
        }
    }

    public function deleteEmployee(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            User::where("id", $employee->user_id)->delete();
            $employee->delete();
        });
    }

    public function getEmployeesByDepartment($departmentId)
    {
        return Employee::with("user")
            ->whereHas("user.roles", function ($query) {
                $query->whereIn("name", ["Employee"]);
            })
            ->whereHas("department", function ($query) use ($departmentId) {
                $query->where("department_id", $departmentId);
            })
            ->get();
    }

    protected function handleFileUpload($file, $path, $previous = "")
    {
        if ($file) {
            return $this->uploads($file, $path, $previous);
        }
        return null;
    }

    protected function attachDepartments($employeeId, $departments)
    {
        foreach ($departments as $departmentId) {
            EmployeeDepartment::create([
                'employee_id' => $employeeId,
                'department_id' => $departmentId,
            ]);
        }
    }
}
