<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Money received or paid against an account transaction or a material
 * invoice, on one day. A row paid in parts has several. Written only through
 * App\Services\TransactionPaymentService, which keeps the parent's status and
 * settled_amount / settled_date in step.
 */
class TransactionPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_transaction_id',
        'project_invoice_detail_id',
        'amount',
        'paid_on',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(AccountTransaction::class, 'account_transaction_id');
    }

    public function invoice()
    {
        return $this->belongsTo(ProjectInvoiceDetail::class, 'project_invoice_detail_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /** The transaction or invoice this payment is against. */
    public function parent(): AccountTransaction|ProjectInvoiceDetail|null
    {
        return $this->account_transaction_id ? $this->transaction : $this->invoice;
    }
}
