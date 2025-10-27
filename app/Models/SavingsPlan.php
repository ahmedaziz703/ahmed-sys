<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Savings Plan model
 *
 * Represents users' savings goals and progress tracking.
 * Each savings plan belongs to a user and tracks target amount and accumulated savings.
 */
class SavingsPlan extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'goal_name',
        'target_amount',
        'saved_amount',
        'target_date',
        'status',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'target_amount' => 'decimal:2',
        'saved_amount' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * The user who owns the savings plan.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}