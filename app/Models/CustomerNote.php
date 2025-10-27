<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer Note model
 * 
 * Represents notes and activities related to customers.
 * Each note belongs to a customer and a user, and can have a specific type.
 */
class CustomerNote extends Model implements Note
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'assigned_user_id',
        'content',
        'type',
        'activity_date',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'activity_date' => 'datetime',
    ];

    /**
     * The customer the note belongs to.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The user who created the note.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user assigned to the note.
     *
     * @return BelongsTo
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}