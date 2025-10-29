<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InverterTypeRate extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function inverter() : BelongsTo {
        return $this->belongsTo(InverterType::class,"inverter_type_id","id")->withTrashed();
    }
}
