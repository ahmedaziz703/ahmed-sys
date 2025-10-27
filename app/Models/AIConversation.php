<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AI Conversation model
 *
 * Represents conversations with AI assistants.
 * Each conversation belongs to a user and can have a title.
 */
class AIConversation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_conversations';

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'is_active',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The user who owns the conversation.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The messages in the conversation.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AIMessage::class, 'conversation_id');
    }
} 