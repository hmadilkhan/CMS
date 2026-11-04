<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;

class AccountTransaction extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'Open';

    public const STATUS_CLOSED = 'Closed';

    protected $fillable = [
        'project_id',
        'project_invoice_detail_id',
        'payee',
        'milestone',
        'amount',
        'deduction_amount',
        'transaction_date',
        'due_date',
        'transaction_details',
        'status',
        'settled_amount',
        'settled_date',
    ];

    /**
     * The built-in payees. The list people pick from is the `payees` table
     * (payeeOptions()), which holds these five plus any added by users; this
     * stays as the fallback for a database without that table.
     */
    public const PAYEES = [
        'sales_partner' => 'Sales Partner',
        'sub_contractor' => 'Sub-Contractor',
        'supplier' => 'Supplier',
        'customer' => 'Customer',
        'others' => 'Others',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    private const PAYEE_OPTIONS_BINDING = 'account_transactions.payee_options';

    /**
     * Every payee as key => label, built-ins first. Read once per request (a
     * report labels every row with it); Payee drops the copy when one is added.
     */
    public static function payeeOptions(): array
    {
        $app = app();

        if (! $app->bound(self::PAYEE_OPTIONS_BINDING)) {
            try {
                $options = Payee::orderByDesc('is_system')->orderBy('id')->pluck('label', 'key')->all();
            } catch (QueryException) {
                $options = [];
            }

            $app->instance(self::PAYEE_OPTIONS_BINDING, $options + self::PAYEES);
        }

        return $app->make(self::PAYEE_OPTIONS_BINDING);
    }

    public static function forgetPayeeOptions(): void
    {
        app()->forgetInstance(self::PAYEE_OPTIONS_BINDING);
    }

    public function getPayeeLabelAttribute()
    {
        return self::payeeOptions()[$this->payee] ?? $this->payee;
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

    /** The invoice a system-raised payable (the Labor Payable) was created for. */
    public function invoice()
    {
        return $this->belongsTo(ProjectInvoiceDetail::class, 'project_invoice_detail_id')->withTrashed();
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
