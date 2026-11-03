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

    protected $casts = [
        'tpo_split' => 'boolean',
    ];

    /**
     * Third Party Credit is entered as a Solar and a Battery TPO credit, and
     * third_party_credit stores their total - so customer_portion is
     * contract_amount - third_party_credit for every row, old or new.
     *
     * A row saved before the split (tpo_split off) that carries a credit keeps
     * its single Third Party Credit figure on the forms and the ledger.
     */
    public function keepsSingleThirdPartyCredit(): bool
    {
        return ! $this->tpo_split && (float) $this->third_party_credit > 0;
    }

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
