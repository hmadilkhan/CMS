<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InverterTypeRate extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function inverter() : BelongsTo {
        return $this->belongsTo(InverterType::class,"inverter_type_id","id");
    }
}
