<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        return view("auth.roles.index", [
            "role" => ($request->id != "" ? Role::findOrFail($request->id) : null),
            "roles" => Role::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->name),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route("role")->with('success', 'Role created successfully.');
    }

    public function update(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->name),
        ]);

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:roles,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($request->id),
            ],
        ]);

        $role = Role::findOrFail($request->id);
        $role->name = $validated['name'];
        $role->save();

        return redirect()->route("role")->with('success', 'Role updated successfully.');
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['id']);

        if ($role->users()->exists()) {
            return response()->json([
                "status" => 422,
                "message" => "This role is assigned to users and cannot be deleted.",
            ], 422);
        }

        $role->delete();

        return response()->json(["status" => 200]);
    }
}
