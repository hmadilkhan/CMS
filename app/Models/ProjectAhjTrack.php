<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAhjTrack extends Model
{
    use HasFactory;

    public const REASON_MANUAL = 'manual';

    public const REASON_MOVED_TO_PERMITTING = 'moved_to_permitting';

    public const REASON_MOVED_OUT = 'moved_out';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function movedToDepartment()
    {
        return $this->belongsTo(Department::class, 'moved_to_department_id');
    }

    public function isManual(): bool
    {
        return $this->removed_reason === self::REASON_MANUAL;
    }
}
