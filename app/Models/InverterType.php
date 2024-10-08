<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InverterType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function invertertyperates()
    {
        return $this->belongsTo(InverterTypeRate::class,"id","inverter_type_id");
    }
}
