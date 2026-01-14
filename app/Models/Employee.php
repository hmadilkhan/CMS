<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\BelongsToManyRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
   use HasFactory, SoftDeletes, LogsActivity;

   protected $guarded = [];

   protected $with = ["user"];

   protected static $logAttributes = ['*']; // Logs all attributes

   protected static $logOnlyDirty = true; // Logs only changed attributes

   protected static $logName = 'Employee'; // Custom log name

   public function getActivitylogOptions(): LogOptions
   {
      return LogOptions::defaults()
         ->setDescriptionForEvent(fn(string $eventName) => "This Employee has been {$eventName}");
   }

   public function user()
   {
      return $this->belongsTo(User::class)->withTrashed();
   }

   // public function department()
   // {
   //    return $this->hasMany(EmployeeDepartment::class);
   // }

   public function department(): BelongsToMany
   {
      return $this->belongsToMany(Department::class, 'employee_departments')->withTrashed();
   }

   public function assignDepartments(): HasMany
   {
      return $this->hasMany(AssignDepartment::class, 'employee_id', 'id');
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
