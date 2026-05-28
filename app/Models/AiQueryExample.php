<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiQueryExample extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'plan',
        'sql',
        'success_count',
        'fail_count',
        'feedback_score',
    ];

    protected $casts = [
        'plan' => 'array',
    ];
}
