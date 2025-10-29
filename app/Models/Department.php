<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = [];

    public function logs() : HasMany {
        return $this->hasMany(ProjectCallLog::class,"department_id","id");
    }

    public function subdepartments() : HasMany {
        return $this->hasMany(SubDepartment::class,"department_id","id")->withTrashed();
    }
}
