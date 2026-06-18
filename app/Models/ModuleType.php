<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleType extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'amount' => 'decimal:2',
        'internal_module_cost' => 'decimal:2',
        'ptc_rating' => 'decimal:2',
        'voc_rating' => 'decimal:2',
        'isc_rating' => 'decimal:2',
        'weight' => 'decimal:2',
        'square_footage' => 'decimal:2',
    ];

    public function inverter()
    {
        return $this->belongsTo(InverterType::class,"inverter_type_id","id")->withTrashed();
    }
}
