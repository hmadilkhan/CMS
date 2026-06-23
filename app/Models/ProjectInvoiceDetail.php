<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'invoice_type',
        'invoice_date',
        'amount',
        'notes',
        'file_name',
        'original_file_name',
        'file_path',
        'uploaded_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
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
