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

    public function logs()
    {
        return $this->hasMany(ProjectCallLog::class,"project_id","id");
    }

    public function notes()
    {
        // return $this->belongsTo(Task::class,"id","project_id")->orderBy("id","DESC")->take(1);
        return $this->hasOne(Task::class)->latestOfMany();
    }

    public function departmentnotes()
    {
        return $this->hasMany(DepartmentNote::class,"project_id","id");
    }

    public function emails()
    {
        return $this->hasMany(Email::class,"project_id","id");
    }

    public function salesPartnerUser()
    {
        return $this->belongsTo(User::class,"sales_partner_user_id","id");
    }

    public function projectAcceptance()
    {
        return $this->belongsTo(ProjectAcceptance::class,"id","project_id")->latest();
    }
}
