<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'payee',
        'milestone',
        'amount',
        'deduction_amount',
        'transaction_date',
        'transaction_details',
    ];

    public function getPayeeLabelAttribute()
    {
        return [
            'sales_partner'  => 'Sales Partner',
            'sub_contractor' => 'Sub-Contractor',
            'others'         => 'Others',
        ][$this->payee] ?? $this->payee;
    }

    public function getRemittedAmountAttribute()
    {
        return $this->amount - $this->deduction_amount;
    }

    // App\Models\Transaction.php
    public function scopeFilterByRole($query, $user)
    {
        if ($user->hasRole('sales_person')) {
            return $query->where('payee', 'sales_partner');
        }

        if ($user->hasRole('sub_contractor_manager')) {
            return $query->where('payee', 'sub_contractor');
        }

        // admin or others → no restriction
        return $query;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
