<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'subject', 'body', 'is_active'])
            ->setDescriptionForEvent(fn (string $eventName) => "This email template has been {$eventName}");
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
