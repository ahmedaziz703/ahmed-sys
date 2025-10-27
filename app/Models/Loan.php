<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Loan model
 * 
 * Represents users' loan transactions.
 * Each loan belongs to a user and can include installment payments and a payment schedule.
 * Tracks loan status, remaining amount, and payment dates.
 */
class Loan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'bank_name',
        'loan_type',
        'amount',
        'monthly_payment',
        'installments',
        'remaining_installments',
        'start_date',
        'next_payment_date',
        'due_date',
        'remaining_amount',
        'status',
        'notes',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installments' => 'integer',
        'remaining_installments' => 'integer',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'due_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * The user who owns the loan.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Transactions related to the loan.
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_id')
            ->where('related_type', self::class);
    }
    
    /**
     * Payments related to the loan.
     * 
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_id')
            ->where('related_type', self::class)
            ->where('category', 'loan_payment')
            ->orderBy('transaction_date', 'asc');
    }
}