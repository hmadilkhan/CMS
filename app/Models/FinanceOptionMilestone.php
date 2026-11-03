<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One payment a finance option raises: what fires it (`trigger_type` plus
 * `trigger_field` or `trigger_zone_id`), how much (`amount_type`/`amount_value`
 * of `amount_base`) and for whom (`payee`). Edited from Operations -> Finance
 * Options; FinanceMilestoneService fires it.
 *
 * `key` is what a fired event is matched on, so it never changes once a row
 * exists - editing a row never makes it fire a second time.
 */
class FinanceOptionMilestone extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function financeOption()
    {
        return $this->belongsTo(FinanceOption::class);
    }

    public function triggerZone()
    {
        return $this->belongsTo(Zone::class, 'trigger_zone_id');
    }
}
