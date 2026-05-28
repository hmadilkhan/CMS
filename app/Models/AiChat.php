<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'openai_response_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class)->oldest();
    }

    public function latestMessages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class)->latest();
    }

    public function queryLogs(): HasMany
    {
        return $this->hasMany(AiQueryLog::class);
    }
}
