<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallScript extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function call()
    {
        return $this->belongsTo(Call::class,'call_id','id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class,'department_id','id');
    }
}
