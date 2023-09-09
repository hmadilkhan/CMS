<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $with = ["files"];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subdepartment()
    {
        return $this->belongsTo(SubDepartment::class,"sub_department_id","id");
    }

    public function assignedPerson()
    {
        return $this->hasMany(Task::class,"project_id","id")->whereIn("status",["In-Progress","Hold","Cancelled"]);
    }

    public function task()
    {
        return $this->hasMany(Task::class,"project_id","id");
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class,"project_id","id");
    }
}
