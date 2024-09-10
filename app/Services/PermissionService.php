<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    public function getPermissionById($id)
    {
        return ($id != "" ? Permission::findOrFail($id) : []);
    }
    public function getPermissionList()
    {
        return Permission::latest()->get();
    }

    public function addPermission($request)
    {
        return Permission::create([
            "name" => $request->name,
            "guard_name" => "web",
        ]);
    }

    public function getRoleById($id)
    {
        return ($id != "" ? Role::findOrFail($id) : []);
    }
    public function getAllRole()
    {
        return  Role::all();
    }

    public function getRolesList()
    {
        return Role::with("permissions")->get();
    }

    public function getUserById($id)
    {
        return ($id != "" ? User::findOrFail($id): []);
    }

    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserPermissionList()
    {
        return User::with("permissions")->get();
    }

}