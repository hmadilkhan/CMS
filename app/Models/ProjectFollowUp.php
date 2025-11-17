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
}
