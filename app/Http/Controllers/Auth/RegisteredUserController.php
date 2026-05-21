<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use App\Traits\MediaTrait;

class RegisteredUserController extends Controller
{
    use MediaTrait;
    /**
     * Display the registration view.
     */
    public function create(Request $request)
    {
        $user = ($request->id != "" ? User::find($request->id) : []);
        $employee = !empty($user) ? Employee::where("user_id", $user->id)->first() : [];

        return view('auth.register', [
            "user" => $user,
            "employee" => $employee,
            "roles" => Role::all(),
            "rolenames" => ($request->id != "" ? $this->getRoleNames($request->id) : []),
            "users" => User::with("type")->orderBy("id","DESC")->get(),
            "types" => UserType::all(),
            "partners" => SalesPartner::all(),
            "contractors" => SubContractor::all(),
            "departments" => Department::all(),
        ]);
    }

    public function getRoleNames($id)
    {
        if ($id) {
            $user = User::find($id);
            return $user->getRoleNames();
        } else {
            return [];
        }
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'array'],
            'employee_code' => [Rule::requiredIf($this->shouldSyncEmployee($request)), 'nullable', 'string', 'max:255', 'unique:' . Employee::class . ',code'],
            'departments' => [Rule::requiredIf($this->shouldSyncEmployee($request)), 'nullable', 'array'],
            'joined_date' => ['nullable', 'date'],
            'overwrite_base_price' => ['nullable', 'numeric'],
            'overwrite_panel_price' => ['nullable', 'numeric'],
        ]);

        DB::transaction(function () use ($request) {
            $result = $this->uploads($request->file, 'users/', "");
            $image = (!empty($result) ? $result["fileName"] : "");

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'username' => $request->username,
                'user_type_id' => $request->user_type_id,
                'sales_partner_id' => ($request->user_type_id == 3 ? $request->sales_partner_id : ($request->user_type_id == 4 ? $request->sub_contractor_id : null)),
                'phone' => $request->phone,
                'address' => $request->address,
                'image' => $image,
                'overwrite_base_price' => $request->overwrite_base_price ?? 0,
                'overwrite_panel_price' => $request->overwrite_panel_price ?? 0,
            ]);

            $user->assignRole($request->role);
            $this->syncEmployeeRecord($user, $request, $image);

            event(new Registered($user));
        });

        return redirect()->back();
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->id);
        $employee = Employee::where("user_id", $user->id)->first();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', 'array'],
            'employee_code' => [
                Rule::requiredIf($this->shouldSyncEmployee($request)),
                'nullable',
                'string',
                'max:255',
                Rule::unique(Employee::class, 'code')->ignore($employee?->id),
            ],
            'departments' => [Rule::requiredIf($this->shouldSyncEmployee($request)), 'nullable', 'array'],
            'joined_date' => ['nullable', 'date'],
            'overwrite_base_price' => ['nullable', 'numeric'],
            'overwrite_panel_price' => ['nullable', 'numeric'],
        ]);

        DB::transaction(function () use ($request, $user) {
            $result = $this->uploads($request->file, 'users/', $request->previous_logo);
            $image = (!empty($result) ? $result["fileName"] : ($request->previous_logo ?? $user->image ?? ""));

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'user_type_id' => $request->user_type_id,
                'sales_partner_id' => ($request->user_type_id == 3 ? $request->sales_partner_id : ($request->user_type_id == 4 ? $request->sub_contractor_id : null)), //$request->sales_partner_id,
                'phone' => $request->phone,
                'address' => $request->address,
                "image" => $image,
                'overwrite_base_price' => $request->overwrite_base_price ?? 0,
                'overwrite_panel_price' => $request->overwrite_panel_price ?? 0,
            ]);

            $user->syncRoles($request->role);
            $this->syncEmployeeRecord($user, $request, $image);
        });

        return redirect()->route("get.register");
    }

    public function delete(Request $request)
    {
        $user = User::findOrFail($request->id);
        if ($user) {
            DB::transaction(function () use ($user) {
                $employee = Employee::where("user_id", $user->id)->first();
                if ($employee) {
                    EmployeeDepartment::where("employee_id", $employee->id)->delete();
                    $employee->delete();
                }
                $user->syncPermissions([]);
                $user->delete();
            });
            return response()->json(["status" => 200]);
        } else {
            return response()->json(["status" => 500]);
        }
    }

    protected function shouldSyncEmployee(Request $request): bool
    {
        $typeName = UserType::where('id', $request->user_type_id)->value('name');
        $hasEmployeeFields = $request->filled('employee_code') || !empty($request->departments);
        $hasExistingEmployee = $request->filled('id') && Employee::where('user_id', $request->id)->exists();
        $hasEmployeeIntent = $request->boolean('sync_employee');

        return $typeName === 'Employee' || $hasEmployeeFields || $hasExistingEmployee || $hasEmployeeIntent;
    }

    protected function syncEmployeeRecord(User $user, Request $request, string $image): void
    {
        if (!$this->shouldSyncEmployee($request)) {
            return;
        }

        $employee = Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $request->name,
                'code' => $request->employee_code,
                'email' => $request->email,
                'phone' => $request->phone ?? '',
                'image' => $image,
                'joined_date' => $request->joined_date,
            ]
        );

        EmployeeDepartment::where("employee_id", $employee->id)->delete();
        foreach ((array) $request->departments as $departmentId) {
            EmployeeDepartment::create([
                'employee_id' => $employee->id,
                'department_id' => $departmentId,
            ]);
        }
    }
}
