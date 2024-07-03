<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailScript extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function email()
    {
        return $this->belongsTo(EmailType::class,'email_type_id','id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class,'department_id','id');
    }
}
