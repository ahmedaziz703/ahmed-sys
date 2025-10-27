<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Debt/Receivable model
 * 
 * Represents the business's receivables from customers and payables to suppliers.
 * Each debt/receivable belongs to a user and is linked to a customer or supplier.
 * Debt/receivable transactions and payments can be tracked.
 */
final class Debt extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'supplier_id',
        'type',
        'amount',
        'currency',
        'buy_price',
        'sell_price',
        'profit_loss',
        'description',
        'date',
        'due_date',
        'status',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * The user who owns the debt/receivable.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The customer associated with the debt/receivable.
     * 
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The supplier associated with the debt/receivable.
     * 
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Transactions related to the debt/receivable.
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id');
    }

    /**
     * Calculate the remaining debt/receivable amount.
     * 
     * @return float Remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        $paidAmount = $this->transactions()
            ->where('status', 'completed')
            ->sum('amount');

        return $this->amount - $paidAmount;
    }

    /**
     * Calculate the profit/loss amount.
     * 
     * @return float Profit/Loss amount
     */
    public function getProfitLossAttribute(): float
    {
        if (!$this->buy_price || !$this->sell_price) {
            return 0;
        }

        return ($this->sell_price - $this->buy_price) * $this->amount;
    }
} 