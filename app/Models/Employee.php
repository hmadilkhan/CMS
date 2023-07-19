<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\BelongsToManyRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
   use HasFactory, SoftDeletes;

   protected $guarded = [];

   protected $with = ["user"];

   public function user()
   {
      return $this->belongsTo(User::class);
   }

   // public function department()
   // {
   //    return $this->hasMany(EmployeeDepartment::class);
   // }

   public function department(): BelongsToMany
   {
      return $this->belongsToMany(Department::class,'employee_departments');
   }

   public function scopeGetUser($query, $departmentId, $roles)
   {
      return $this->where("department_id", $departmentId)
         // ->with("user","user.roles")
         ->whereHas("user", function ($query) use ($roles) {
            $query->whereHas('roles', function ($q) use ($roles) {
               $q->whereIn('roles.name', $roles);
            });
         });
   }

   public function scopeGetUserWithRoleAndDepartment($query, $departmentId)
   {
      return $this->where("department_id", $departmentId)
         // ->with("user","user.roles")
         ->whereHas("user", function ($query) {
            $query->whereHas('roles', function ($q) {
               $q->whereIn('roles.name', ["Employee"]);
            });
         });
   }
}
