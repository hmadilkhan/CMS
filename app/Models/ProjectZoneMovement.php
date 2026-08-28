<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One zone change. The full set for a project is its zone history, and the
 * distinct `to_zone_id` values are the zones whose tabs the project page shows.
 */
class ProjectZoneMovement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_auto' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function fromZone()
    {
        return $this->belongsTo(Zone::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(Zone::class, 'to_zone_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
