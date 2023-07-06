<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerFinance extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    protected $with = ['finance','term','apr'];

    public function finance()
    {
        return $this->belongsTo(FinanceOption::class,"finance_option_id","id");
    }

    public function term()
    {
        return $this->belongsTo(LoanTerm::class,"loan_term_id","id");
    }

    public function apr()
    {
        return $this->belongsTo(LoanApr::class,"loan_apr_id","id");
    }
}


