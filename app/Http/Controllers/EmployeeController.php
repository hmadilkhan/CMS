<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of the employees.
     */
    public function index()
    {
        $employees = $this->employeeService->getAllEmployeesWithDepartments();
        return view("employees.index", compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $data = $this->employeeService->getFormCreateData();
        return view("employees.form", $data);
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $employee = $this->employeeService->createEmployee($request);
            return response()->json(["status" => 200, "message" => "Employee created successfully"]);
        } catch (\Exception $e) {
            return response()->json(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing an employee.
     */
    public function edit(Employee $employee)
    {
        $data = $this->employeeService->getFormEditData($employee);
        return view("employees.form", $data);
    }

    /**
     * Update the employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        // return $request->validated();
        try {
            return $this->employeeService->updateEmployee($employee, $request);
            // return response()->json(["status" => 200, "message" => "Employee updated successfully"]);
        } catch (\Exception $e) {
            return response()->json(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    /**
     * Remove the employee.
     */
    public function destroy(Employee $employee)
    {
        try {
            $this->employeeService->deleteEmployee($employee);
            return response()->json(["status" => 200, "message" => "Employee deleted successfully"]);
        } catch (\Exception $e) {
            return response()->json(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    /**
     * Get employees by department.
     */
    public function getDepartmentEmployees(Request $request)
    {
        if ($request->id) {
            $employees = $this->employeeService->getEmployeesByDepartment($request->id);
            return response()->json(["status" => 200, "employees" => $employees]);
        }
    }
}
