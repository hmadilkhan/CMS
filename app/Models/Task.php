<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
     * How long a stint lasted, in seconds. An open stint is measured up to now,
     * and a negative span (clock skew, a hand-edited row) reads as instant
     * rather than as time travel.
     *
     * This is the primitive the other two build on: a per-department total sums
     * SECONDS and converts once, so rounding never accumulates across stints.
     */
    public static function stintSeconds($entryDate, ?Carbon $exitDate): int
    {
        $entry = Carbon::parse($entryDate);
        $exit = $exitDate ?? Carbon::now();

        return max(0, $entry->diffInSeconds($exit, false));
    }

    /**
     * How long a stint lasted, in days to one decimal place.
     *
     * This used to be `max(1, diffInDays(...))`, which was wrong twice over: a
     * 44-second stint read as a whole day, and `diffInDays` truncates, so three
     * days and 23 hours read as three. The two errors pull opposite ways and
     * both are invisible in the total.
     */
    public static function stintDays($entryDate, ?Carbon $exitDate): float
    {
        return self::daysFromSeconds(self::stintSeconds($entryDate, $exitDate));
    }

    /**
     * Seconds as days to one decimal place. Per-department totals convert their
     * summed seconds through here.
     */
    public static function daysFromSeconds(int $seconds): float
    {
        return round($seconds / 86400, 1);
    }

    /**
     * A stint as text a person reads: "< 1 hour", "6 hours", "3 days 19 hours",
     * "4 days".
     *
     * The hour part is the remainder AFTER whole days, so it is always 0-23 -
     * "3 days 24 hours" cannot happen, that span is four days. It is computed
     * from seconds, never from the decimal in `stintDays()`: 3.5 days is three
     * days and TWELVE hours, not five.
     *
     * Both parts truncate. "3 days 19 hours" means at least that much, which is
     * how a duration is normally read, and truncating also removes the 23h59m
     * case that rounding would have to carry into an extra day.
     */
    public static function stintLabel($entryDate, ?Carbon $exitDate): string
    {
        $seconds = self::stintSeconds($entryDate, $exitDate);

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);

        if ($days === 0) {
            return $hours === 0 ? '< 1 hour' : $hours.' '.Str::plural('hour', $hours);
        }

        $label = $days.' '.Str::plural('day', $days);

        return $hours === 0 ? $label : $label.' '.$hours.' '.Str::plural('hour', $hours);
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
