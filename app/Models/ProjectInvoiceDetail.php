<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The two invoice types a project carries. Which move waits for which is
     * Project::INVOICE_GATES; the commission waits for both.
     */
    public const REQUIRED_TYPES = [
        'labor' => 'Labor',
        'material' => 'Material',
    ];

    protected $fillable = [
        'project_id',
        'invoice_type',
        'invoice_date',
        'amount',
        'status',
        'settled_amount',
        'settled_date',
        'pay_run_date',
        'notes',
        'file_name',
        'original_file_name',
        'file_path',
        'uploaded_by',
    ];

    /** A material invoice is paid from the Transaction Portal; labor goes through its payable. */
    public const STATUS_OPEN = 'Open';

    public const STATUS_CLOSED = 'Closed';

    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'settled_date' => 'date',
        'pay_run_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /**
     * Keep the project's actual labor / material cost equal to its invoices of
     * that type. Called wherever an invoice is added or removed (the project's
     * Invoices tab, the Transaction Portal).
     */
    public static function syncProjectCost(Project $project, string $invoiceType): void
    {
        $column = [
            'labor' => 'actual_labor_cost',
            'material' => 'actual_material_cost',
        ][$invoiceType] ?? null;

        if (! $column) {
            return;
        }

        $project->update([
            $column => self::where('project_id', $project->id)
                ->where('invoice_type', $invoiceType)
                ->sum('amount'),
        ]);
    }

    /** Money paid against it, oldest first (several when paid in parts). */
    public function payments()
    {
        return $this->hasMany(TransactionPayment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function getInvoiceTypeLabelAttribute(): string
    {
        return [
            'labor' => 'Labor',
            'material' => 'Material',
        ][$this->invoice_type] ?? $this->invoice_type;
    }
}
