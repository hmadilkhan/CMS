<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        return view('auth.register', [
            "user" => ($request->id != "" ? User::find($request->id) : []),
            "roles" => Role::all(),
            "rolenames" => ($request->id != "" ? $this->getRoleNames($request->id) : []),
            "users" => User::with("type")->orderBy("id","DESC")->get(),
            "types" => UserType::all(),
            "partners" => SalesPartner::all(),
            "contractors" => SubContractor::all(),
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $result = $this->uploads($request->file, 'users/', "");
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'username' => $request->username,
            'user_type_id' => $request->user_type_id,
            'sales_partner_id' => ($request->user_type_id == 3 ? $request->sales_partner_id : ($request->user_type_id == 4 ? $request->sub_contractor_id : null)),
            'phone' => $request->phone,
            'image' => (!empty($result) ? $result["fileName"] : ""),
        ]);
        $user->assignRole($request->role);
        event(new Registered($user));
        return redirect()->back();
    }

    protected function update(Request $request)
    {
        $result = $this->uploads($request->file, 'users/', $request->previous_logo);
        $user = User::where("id", $request->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'user_type_id' => $request->user_type_id,
            'sales_partner_id' => ($request->user_type_id == 3 ? $request->sales_partner_id : ($request->user_type_id == 4 ? $request->sub_contractor_id : null)), //$request->sales_partner_id,
            'phone' => $request->phone,
            "image" => (!empty($result) ? $result["fileName"] : $request->previous_logo),
        ]);
        $user = User::findOrFail($request->id);
        $user->syncRoles($request->role);
        return redirect()->route("get.register");
    }

    public function delete(Request $request)
    {
        $user = User::findOrFail($request->id);
        if ($user) {
            DB::transaction(function () use ($user) {
                $user->syncPermissions([]);
                $user->delete();
            });
            return response()->json(["status" => 200]);
        } else {
            return response()->json(["status" => 500]);
        }
    }
}
