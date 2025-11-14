<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $with = ["files"];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnlyDirty()
    //         ->logAll()
    //         ->useLogName('project')
    //         ->setDescriptionForEvent(function (string $eventName) {
    //             $changedAttributes = collect($this->getDirty())->keys()->implode(', ');
    //             $userName = auth()->user() ? auth()->user()->name : 'System'; // Get user name or "System" if not available

    //             return "{$userName} has {$eventName} the project" . 
    //                    ($changedAttributes ? " with the following updates: {$changedAttributes}" : ".");
    //         });
    // }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function subdepartment()
    {
        return $this->belongsTo(SubDepartment::class, "sub_department_id", "id");
    }

    public function assignedPerson()
    {
        return $this->hasMany(Task::class, "project_id", "id")->whereIn("status", ["In-Progress", "Hold", "Cancelled"]);
    }

    public function task()
    {
        return $this->hasMany(Task::class, "project_id", "id");
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class, "project_id", "id");
    }

    public function logs()
    {
        return $this->hasMany(ProjectCallLog::class, "project_id", "id");
    }

    public function notes()
    {
        // return $this->belongsTo(Task::class,"id","project_id")->orderBy("id","DESC")->take(1);
        return $this->hasOne(Task::class)->latestOfMany();
    }

    public function departmentnotes()
    {
        return $this->hasMany(DepartmentNote::class, "project_id", "id");
    }

    public function emails()
    {
        return $this->hasMany(Email::class, "project_id", "id");
    }

    public function salesPartnerUser()
    {
        return $this->belongsTo(User::class, "sales_partner_user_id", "id")->withTrashed();
    }
    public function subContractorUser()
    {
        return $this->belongsTo(User::class, "sub_contractor_user_id", "id")->withTrashed();
    }

    public function projectAcceptance()
    {
        // return $this->belongsTo(ProjectAcceptance::class, "id", "project_id")->latest();
        return $this->hasOne(ProjectAcceptance::class, 'project_id', 'id')->latest();
    }

    public function addersLocks()
    {
        return $this->hasMany(ProjectAddersLock::class, 'project_id', 'id');
    }

    public function serviceTickets()
    {
        return $this->hasMany(ServiceTicket::class, 'project_id', 'id');
    }

    public function siteSurvey()
    {
        return $this->hasOne(SiteSurvey::class, 'project_id', 'id');
    }
}
