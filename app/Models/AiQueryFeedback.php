<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQueryFeedback extends Model
{
    use HasFactory;

    protected $table = 'ai_query_feedback';

    protected $fillable = [
        'ai_chat_message_id',
        'user_id',
        'rating',
        'comment',
        'expected_result',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'ai_chat_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
