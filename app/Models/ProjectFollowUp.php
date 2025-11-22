<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'employee_id',
        'created_by',
        'department_id',
        'sub_department_id',
        'follow_up_date',
        'notes',
        'status',
        'resolved_date',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'resolved_date' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class);
    }
}
