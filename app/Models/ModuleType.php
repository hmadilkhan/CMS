<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleType extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function inverter()
    {
        return $this->belongsTo(InverterType::class,"inverter_type_id","id")->withTrashed();
    }
}
