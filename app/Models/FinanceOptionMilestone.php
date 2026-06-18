<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceOptionMilestone extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function financeOption()
    {
        return $this->belongsTo(FinanceOption::class);
    }
}
