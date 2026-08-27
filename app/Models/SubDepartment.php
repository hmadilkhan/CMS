<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubDepartment extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'show_in_move_list' => 'boolean',
    ];

    public function department(): BelongsTo {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    /**
     * Lanes a user may pick as a move destination. A closed lane is reached and
     * left only by the system, never from a move dropdown.
     */
    public function scopeSelectableForMove($query)
    {
        return $query->where("show_in_move_list", true);
    }

    /** A project sitting in a closed lane cannot be moved by hand. */
    public function isClosedForMovement(): bool
    {
        return ! $this->show_in_move_list;
    }
}
