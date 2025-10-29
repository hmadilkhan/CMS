<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanApr extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = [];

    public function loan() : BelongsTo {
        return $this->belongsTo(LoanTerm::class,"loan_term_id","id")->withTrashed();
    }

    public function finance() : BelongsTo {
        return $this->belongsTo(FinanceOption::class,"finance_option_id","id")->withTrashed();
    }
}
