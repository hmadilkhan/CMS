<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * When this stint in the department ended, or null while it is still open.
     *
     * `completed_at` is authoritative - it is stamped once, at the moment the
     * row is closed out, and nothing writes it again. Rows written before that
     * column existed (and that the backfill could not reconstruct) fall back to
     * `updated_at`, which is only an approximation: it is the row's LAST write,
     * so a later mass update - a customer edit re-stamping every Deal Review
     * task, a project status change - drags it forward and inflates the stint.
     *
     * Takes the three values rather than a model so the raw rows the reporting
     * queries select can use the same rule.
     */
    public static function exitDate(?string $status, $completedAt, $updatedAt): ?Carbon
    {
        if (! empty($completedAt)) {
            return Carbon::parse($completedAt);
        }

        if ($status === 'In-Progress') {
            return null;
        }

        return empty($updatedAt) ? null : Carbon::parse($updatedAt);
    }

    /**
     * Whole days a stint lasted, floored at 1 so a same-day stint still reads
     * as a day. An open stint is measured up to now.
     */
    public static function stintDays($entryDate, ?Carbon $exitDate): int
    {
        return max(1, (int) Carbon::parse($entryDate)->diffInDays($exitDate ?? Carbon::now()));
    }

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
