<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\Commission\CommissionService;
use App\Enums\TransactionTypeEnum;
/**
 * Transaction model
 * 
 * Represents financial transactions (income, expense, transfer, payment, etc.).
 * Each transaction belongs to a user and can be associated with various accounts, customers, or suppliers.
 * Supports tax, withholding, installment, and subscription features.
 */
class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Transaction types
     */
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_LOAN_PAYMENT = 'loan_payment';
    const TYPE_DEBT_PAYMENT = 'debt_payment';
    const TYPE_CREDIT_PAYMENT = 'payment';
    const TYPE_ATM_DEPOSIT = 'atm_deposit';
    const TYPE_ATM_WITHDRAW = 'atm_withdraw';
    

    /**
     * Payment types
     */
    const PAYMENT_CASH = 'cash';
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_CREDIT_CARD = 'credit_card';

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'try_equivalent' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'next_payment_date' => 'date',
        'is_subscription' => 'boolean',
        'auto_renew' => 'boolean',
        'is_taxable' => 'boolean',
        'has_withholding' => 'boolean',
        'payment_method' => PaymentMethodEnum::class,
        'reference_id' => 'string',
    ];

    /**
     * Payment method enum values
     */
    public const PAYMENT_METHOD_CASH = PaymentMethodEnum::CASH->value;
    public const PAYMENT_METHOD_BANK = PaymentMethodEnum::BANK->value;
    public const PAYMENT_METHOD_CREDIT_CARD = PaymentMethodEnum::CREDIT_CARD->value;
    public const PAYMENT_METHOD_CRYPTO = PaymentMethodEnum::CRYPTO->value;
    public const PAYMENT_METHOD_VIRTUAL_POS = PaymentMethodEnum::VIRTUAL_POS->value;

    /**
     * Get payment method options.
     * 
     * @return array<string, string>
     */
    public static function paymentMethods(): array
    {
        return PaymentMethodEnum::toArray();
    }

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'source_account_id',
        'destination_account_id',
        'customer_id',
        'supplier_id',
        'type',
        'amount',
        'currency',
        'exchange_rate',
        'try_equivalent',
        'fee_amount',
        'date',
        'payment_method',
        'description',
        'installments',
        'remaining_installments',
        'monthly_amount',
        'next_payment_date',
        'is_subscription',
        'subscription_period',
        'auto_renew',
        'is_taxable',
        'tax_rate',
        'tax_amount',
        'has_withholding',
        'withholding_rate',
        'withholding_amount',
        'reference_id',
        'status',
    ];

    /**
     * The user who owns the transaction.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The category of the transaction.
     * 
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The source account of the transaction.
     * 
     * @return BelongsTo
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    /**
     * The destination account of the transaction.
     * 
     * @return BelongsTo
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    /**
     * The customer of the transaction.
     * 
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The supplier of the transaction.
     * 
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Reference transaction
     * 
     * @return BelongsTo
     */
    public function referenceTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reference_id');
    }

    /**
     * Related transactions
     * 
     * @return HasMany
     */
    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id');
    }

    /**
     * Transfer account for the transaction.
     *
     * @return BelongsTo
     */
    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_account_id');
    }

    /**
     * Related transfer transaction.
     *
     * @return HasOne
     */
    public function relatedTransfer(): HasOne
    {
        return $this->hasOne(Transaction::class, 'reference_id', 'reference_id')
            ->where('id', '!=', $this->id);
    }

    /**
     * Observer methods for model events.
     * 
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (!isset($transaction->status)) {
                $transaction->status = 'completed';
            }
            
            // Calculate TRY equivalent
            if (!isset($transaction->try_equivalent)) {
                if ($transaction->currency !== 'YER') {
                    $exchangeRate = $transaction->exchange_rate ?? 1;
                    $transaction->try_equivalent = $transaction->amount * $exchangeRate;
                } else {
                    $transaction->try_equivalent = $transaction->amount;
                }
            }
            
            // Other calculations...
            if ($transaction->is_taxable && $transaction->tax_rate) {
                $taxRate = $transaction->tax_rate / 100;
                $netAmount = $transaction->amount / (1 + $taxRate);
                $transaction->tax_amount = round($transaction->amount - $netAmount, 2);
            }
            
            if ($transaction->is_taxable && $transaction->has_withholding && $transaction->withholding_rate) {
                $withholdingRate = $transaction->withholding_rate / 100;
                $transaction->withholding_amount = round($transaction->amount * $withholdingRate, 2);
            }
            
            if (!isset($transaction->date)) {
                $transaction->date = $transaction->transaction_date ?? now();
            }
        });

        static::updating(function ($transaction) {
            if ($transaction->currency !== 'YER') {
                $transaction->try_equivalent = $transaction->amount * ($transaction->exchange_rate ?? 1);
            } else {
                $transaction->try_equivalent = $transaction->amount;
            }
            if ($transaction->is_taxable && $transaction->tax_rate) {
                $taxRate = $transaction->tax_rate / 100;
                $netAmount = $transaction->amount / (1 + $taxRate);
                $transaction->tax_amount = round($transaction->amount - $netAmount, 2);
            } else {
                $transaction->tax_amount = null;
            }
            if ($transaction->is_taxable && $transaction->has_withholding && $transaction->withholding_rate) {
                $withholdingRate = $transaction->withholding_rate / 100;
                $transaction->withholding_amount = round($transaction->amount * $withholdingRate, 2);
            } else {
                $transaction->withholding_amount = null;
            }

            // If income transaction and amount changed, update the commission
            if ($transaction->type === TransactionTypeEnum::INCOME->value && $transaction->isDirty('amount')) {
                if ($transaction->commission) {
                    $commission = $transaction->commission;
                    $commission->commission_amount = $transaction->amount * ($commission->commission_rate / 100);
                    $commission->save();
                }
            }
        });

        static::created(function ($transaction) {
            // Calculate commission only for income transactions
            if ($transaction->type === TransactionTypeEnum::INCOME->value) {
                app(CommissionService::class)->createCommissionForTransaction($transaction);
            }
        });

        static::deleted(function ($transaction) {
            // Delete the related commission when the transaction is deleted
            if ($transaction->commission) {
                $transaction->commission->delete();
            }
        });
    }

    /**
     * Commission related to the transaction
     */
    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }
}