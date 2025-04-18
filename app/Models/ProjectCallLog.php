<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCallLog extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function call() : BelongsTo {
        return $this->belongsTo(Call::class,"call_no","id");
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class,"user_id","id");
    }

}
