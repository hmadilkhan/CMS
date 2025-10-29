<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adder extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function type() : BelongsTo {
        return $this->belongsTo(AdderType::class,"adder_type_id","id")->withTrashed();
    }

    public function subtype() : BelongsTo {
        return $this->belongsTo(AdderSubType::class,"adder_sub_type_id","id");
    }

    public function unit() : BelongsTo {
        return $this->belongsTo(AdderUnit::class,"adder_unit_id","id")->withTrashed();
    }
}
