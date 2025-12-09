<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAcceptance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'inverter_base_price' => 'decimal:2',
        'dealer_fee_amount' => 'decimal:2',
        'module_qty_price' => 'decimal:2',
        'modules_amount' => 'decimal:2',
        'contract_amount' => 'decimal:2',
        'redline_costs' => 'decimal:2',
        'adders_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'adders_list' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "action_by", "id");
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
