<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A funding-side lane: Pre NTP, NTP, M1, M2, Archived.
 *
 * The five rows are seeded by migration and are not managed from the UI - the
 * module's whole flow (auto entry, auto promotion, the hidden archive) is keyed
 * off their slugs.
 */
class Zone extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'show_in_list' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /** The lanes the Zones board draws, in pipeline order. Archived is not one. */
    public function scopeOnBoard($query)
    {
        return $query->where('show_in_list', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function isArchive(): bool
    {
        return $this->slug === config('zones.archived_zone', 'archived');
    }
}
