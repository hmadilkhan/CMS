<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDepartment extends Model
{
    use HasFactory;
    protected $guarded = [];

    function department() {
        return $this->belongsTo(Department::class);
    }

    function employee() {
        return $this->belongsTo(Employee::class);
    }
}
