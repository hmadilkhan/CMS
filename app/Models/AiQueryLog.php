<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQueryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_chat_id',
        'user_id',
        'provider',
        'model',
        'status',
        'response_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'duration_ms',
        'request_payload',
        'response_payload',
        'error_message',
        'openai_calls',
        'openai_ms',
        'db_ms',
        'engine',
        'fallbacks',
        'stage_timings',
        'question_hash',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'stage_timings' => 'array',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(AiChat::class, 'ai_chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
