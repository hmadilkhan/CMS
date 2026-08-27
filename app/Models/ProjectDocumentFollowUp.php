<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDocumentFollowUp extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_RESOLVED = 'Resolved';

    /** The missing document arrived (meter spot result / utility bill upload). */
    public const REASON_DOCUMENT_RECEIVED = 'document_received';

    /** The question no longer applies - MPU is not required, the bill answer changed. */
    public const REASON_NOT_REQUIRED = 'not_required';

    /** The project was archived / cancelled while the chase was open. */
    public const REASON_PROJECT_ARCHIVED = 'project_archived';

    /**
     * Written by a migration, never by the app: the project already answered
     * the chase's question before the chase existed, so it is left alone.
     * Deleting such a row lets the chase open for that project after all.
     */
    public const REASON_PRE_EXISTING = 'pre_existing';

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

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
