<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\User;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmployeeController extends Controller
{
    use MediaTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $employee = Employee::with("user")
        // ->whereHas("user.roles",function($query){
        //     $query->whereIn("name", ["Employee"]);
        // })
        // ->whereHas("department",function($query){
        //     $query->whereIn("department_id", [2]);
        // })
        // ->get();
        // return $employee;

        // return Employee::with("user","user.roles","department")->get();
        return view("employees.index", [
            "employees" => Employee::with("department")->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("employees.form", [
            "employee" => [],
            "roles" => Role::all(),
            "departments" => Department::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required',
            'departments' => 'required',
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            DB::beginTransaction();
            $result = $this->uploads($request->file, 'employees/');
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'username' => $request->username,
                'user_type_id' => 2, // 2 is for Employee
            ]);
            $user->assignRole($request->roles);
            // foreach ($request->roles as $key => $value) {
            //     $user->syncRoles($value);
            // }
            $employee = Employee::create(
                array_merge(
                    $request->except(["file", "id", "previous_logo", "roles", "username", "password", "password_confirmation", "user_id","departments"]),
                    [
                        "user_id" => $user->id,
                        "image" => (!empty($result) ? $result["fileName"] : ""),
                    ]
                )
            );
            foreach ($request->departments as $key => $value) {
                EmployeeDepartment::create([
                    "employee_id" => $employee->id,
                    "department_id" => $value,
                ]);
            }
            DB::commit();
            return response()->json(["status" => 200, "messsage" => "Employee created successfully"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "messsage" => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return view("employees.form", [
            "employee" => $employee,
            "roles" => Role::all(),
            "departments" => Department::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        try {
            DB::beginTransaction();
            $result = $this->uploads($request->file, 'employees/', $request->previous_logo);
            User::where("id", $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            $user = User::findOrFail($request->user_id);
            $user->syncRoles($request->roles);
            $employee->update(
                array_merge(
                    $request->except(["file", "id", "previous_logo", "roles", "username", "password", "password_confirmation", "user_id","departments"]),
                    [
                        "user_id" => $request->user_id,
                        "image" => (!empty($result) ? $result["fileName"] : $request->previous_logo),
                    ]
                )
            );
            EmployeeDepartment::where("employee_id",$employee->id)->delete();
            foreach ($request->departments as $key => $value) {
                EmployeeDepartment::create([
                    "employee_id" => $employee->id,
                    "department_id" => $value,
                ]);
            }
            DB::commit();
            return response()->json(["status" => 200, "messsage" => "Employee updated successfully"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "messsage" => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    function getDepartmentEmployees(Request $request){
        if ($request->id != "") {
            $employees = Employee::with("user")
            ->whereHas("user.roles",function($query){
                $query->whereIn("name", ["Employee"]);
            })
            ->whereHas("department",function($query) use ($request){
                $query->whereIn("department_id", [$request->id]);
            })
            ->get();
            return response()->json(["status" => 200,"employees" => $employees]);
        }
    }
}
