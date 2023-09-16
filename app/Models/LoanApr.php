<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApr extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function loan() : BelongsTo {
        return $this->belongsTo(LoanTerm::class,"loan_term_id","id");
    }
}
