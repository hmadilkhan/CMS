<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use HasFactory, SoftDeletes;

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

    public function getFormattedAmountAttribute()
    {
        return '$ ' . number_format($this->attributes['amount'], 2);
    }

    public function getFormattedDeductionAmountAttribute()
    {
        return '$ ' . number_format($this->attributes['deduction_amount'], 2);
    }

    public function getRemittedAmountAttribute()
    {
        return $this->attributes['amount'] - $this->attributes['deduction_amount'];
    }

    public function getFormattedRemittedAmountAttribute()
    {
        return '$ ' . number_format($this->remitted_amount, 2);
    }
    
    public function getFormattedTransactionDateAttribute()
    {
        return date("d M Y",strtotime($this->attributes['transaction_date']));
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    // App\Models\Transaction.php
    public function scopeFilterByRole($query, $user)
    {
        if ($user->hasRole('Sales Person')) {
            return $query->whereHas('project.customer', function ($q) {
                $q->where('sales_partner_id', auth()->user()->sales_partner_id);
            })->where('payee', 'sales_partner');
        }

        if ($user->hasRole('Sub-Contractor Manager')) {
            return $query->whereHas('project.customer', function ($q) {
                $q->where('sub_contractor_id', auth()->user()->sales_partner_id);
            })->where('payee', 'sub_contractor');
        }

        // admin or others → no restriction
        return $query;
    }
}
