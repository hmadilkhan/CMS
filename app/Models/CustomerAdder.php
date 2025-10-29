<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAdder extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    protected $with = ['type','subtype','unit'];

    public function type()
    {
        return $this->belongsTo(AdderType::class,"adder_type_id","id")->withTrashed();
    }

    public function subtype()
    {
        return $this->belongsTo(AdderSubType::class,"adder_sub_type_id","id");
    }

    public function unit()
    {
        return $this->belongsTo(AdderUnit::class,"adder_unit_id","id")->withTrashed();
    }
}
