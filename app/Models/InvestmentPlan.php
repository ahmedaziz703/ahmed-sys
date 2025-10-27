<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Investment Plan model
 * 
 * Represents users' investment plans and portfolios.
 * Each investment plan belongs to a user and can have different investment types.
 * Tracks invested amount, current value, and investment date.
 */
final class InvestmentPlan extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'investment_name',
        'invested_amount',
        'current_value',
        'investment_type',
        'investment_date',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'invested_amount' => 'decimal:2',
        'current_value' => 'decimal:2',
        'investment_date' => 'date',
    ];

    /**
     * The user who owns the investment plan.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}