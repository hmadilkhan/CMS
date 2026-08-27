<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDocumentFollowUp extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_RESOLVED = 'Resolved';

    /** Meter spot result was filled in - the document is no longer missing. */
    public const REASON_METER_SPOT_RESULT = 'meter_spot_result';

    /** MPU Required was switched away from "yes", so the follow up no longer applies. */
    public const REASON_MPU_NOT_REQUIRED = 'mpu_not_required';

    /** The project was archived / cancelled while the chase was open. */
    public const REASON_PROJECT_ARCHIVED = 'project_archived';

    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
