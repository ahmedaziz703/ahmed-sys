<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * AI Message model
 *
 * Represents messages in AI conversations.
 * Each message belongs to a conversation and can have a role and content.
 */
class AIMessage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_messages';

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The conversation the message belongs to.
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AIConversation::class);
    }
} 