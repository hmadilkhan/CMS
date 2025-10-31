<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class, "project_id", "id")->withTrashed();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "employee_id", "id");
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function subdepartment()
    {
        return $this->belongsTo(SubDepartment::class, "sub_department_id", "id");
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class, "task_id", "id");
    }
}
