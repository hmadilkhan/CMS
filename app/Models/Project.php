<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guarded = [];

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
        return $this->hasMany(Task::class,"project_id","id")->where("status","In-Progress");
    }

    public function task()
    {
        return $this->hasMany(Task::class,"project_id","id");
    }
}
