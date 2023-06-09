<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        return view('auth.register',[
            "user" => ($request->id != "" ? User::find($request->id) : []),
            "roles" => Role::all(),
            "rolenames" =>  ($request->id != "" ? $this->getRoleNames($request->id) : []),
            "users" => User::with("type")->get(),
            "types" => UserType::all(),
        ]);
    }

    public function getRoleNames($id)
    {
        if ($id) {
            $user = User::find($id);
            return $user->getRoleNames();
        }else{
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'username' => $request->username,
            'user_type_id' => $request->user_type_id,
        ]);
        $user->assignRole($request->role);

        event(new Registered($user));
        return redirect()->back();
    }

    protected function update(Request $request)
    {
        $user = User::where("id", $request->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'user_type_id' => $request->user_type_id,
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
