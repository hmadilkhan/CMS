<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A zone's own approval date for one project ("M1 Approval Date"). Filed from
 * that zone's tab; zones with `has_approval_date` collect it. See docs/zones.md.
 */
class ProjectZoneDate extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
