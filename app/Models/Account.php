<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Account model
 * 
 * Represents users' financial accounts.
 * Manages bank accounts, credit cards, crypto wallets, virtual POS, and cash accounts.
 */
class Account extends Model
{
    use HasFactory, SoftDeletes;

    /** @var string Bank account type */
    const TYPE_BANK_ACCOUNT = 'bank_account';

    /** @var string Credit card type */
    const TYPE_CREDIT_CARD = 'credit_card';

    /** @var string Crypto wallet type */
    const TYPE_CRYPTO_WALLET = 'crypto_wallet';

    /** @var string Virtual POS type */
    const TYPE_VIRTUAL_POS = 'virtual_pos';

    /** @var string Cash type */
    const TYPE_CASH = 'cash';

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'currency',
        'balance',
        'details',
        'status',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'float',
        'details' => 'array',
        'status' => 'boolean',
    ];

    /**
     * Ensure non-negative balance for credit cards while setting the balance attribute.
     */
    public function setBalanceAttribute($value)
    {
        if ($this->type === self::TYPE_CREDIT_CARD) {
            // Credit card debt balance cannot be negative
            $this->attributes['balance'] = max(0, $value);
        } else {
            $this->attributes['balance'] = $value;
        }
    }

    /**
     * The user who owns the account.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transactions made from the account (as source).
     * 
     * @return HasMany
     */
    public function sourceTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'source_account_id');
    }

    /**
     * Transactions made to the account (as destination).
     * 
     * @return HasMany
     */
    public function destinationTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    /**
    * All transactions related to the account.
    * 
    * @return HasMany
    */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'source_account_id')
            ->orWhere('destination_account_id', $this->id)
            ->orderBy('date', 'desc');
    }

    /**
     * Calculate the account balance in TRY.
     * 
     * @return float Balance in TRY
     */
    public function calculateTryBalance(): float
    {
        return $this->transactions()
            ->where(function ($query) {
                $query->where('source_account_id', $this->id)
                    ->orWhere('destination_account_id', $this->id);
            })
            ->get()
            ->reduce(function ($balance, $transaction) {
                if ($transaction->source_account_id === $this->id) {
                    return $balance - $transaction->try_equivalent;
                }
                return $balance + $transaction->try_equivalent;
            }, 0);
    }

    /**
     * Get the formatted balance string.
     * 
     * @return string Formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        $balance = $this->balance;
        return number_format($balance, 2, ',', '.') . ' ' . $this->currency;
    }
}