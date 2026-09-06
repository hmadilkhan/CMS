<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'report_type',
        'selected_fields',
        'group_by',
        'group_by_2',
        'summaries',
        'filters',
        'calculated_fields',
        'query',
        'user_id'
    ];

    protected $casts = [
        'selected_fields' => 'array',
        'summaries' => 'array',
        'filters' => 'array',
        'calculated_fields' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
