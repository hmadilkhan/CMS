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
        return [
            "employee" => $employee,
            "roles" => Role::all(),
            "departments" => Department::all(),
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
            $user->assignRole($data['roles']);

            $employee = Employee::create(array_merge($data->except(["file", "id", "previous_logo", "roles", "username", "password", "password_confirmation", "user_id", "departments", "overwrite_base_price", "overwrite_panel_price"]), ['user_id' => $user->id, 'image' => $result]));

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
        // Similar to createEmployee but updating an existing employee.
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

    protected function handleFileUpload($file, $path)
    {
        if ($file) {
            return $this->uploads($file, $path);
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
