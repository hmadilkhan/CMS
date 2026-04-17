<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdderType extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function getTagListAttribute(): array
    {
        return empty($this->tag) ? [] : [$this->tag];
    }
}
