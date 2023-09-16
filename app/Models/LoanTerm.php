<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanTerm extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function finance() : BelongsTo {
        return $this->belongsTo(FinanceOption::class,"finance_option_id","id");
    }
}
