<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFinanceMilestoneEvent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'email_recipients' => 'array',
        'triggered_at' => 'datetime',
    ];

    public function accountTransaction()
    {
        return $this->belongsTo(AccountTransaction::class);
    }

    public function milestone()
    {
        return $this->belongsTo(FinanceOptionMilestone::class, 'finance_option_milestone_id');
    }
}
