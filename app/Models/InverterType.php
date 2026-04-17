<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InverterType extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    public function invertertyperates()
    {
        return $this->belongsTo(InverterTypeRate::class,"id","inverter_type_id")->withTrashed();
    }

    public function getTagListAttribute(): array
    {
        return array_values(array_filter($this->tags ?? []));
    }
}
