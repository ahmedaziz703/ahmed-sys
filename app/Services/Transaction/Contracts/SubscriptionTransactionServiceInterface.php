<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Subscription transactions service interface
 * 
 * Defines methods required to manage subscription transactions,
 * including creation, renewal, and overall management.
 */
interface SubscriptionTransactionServiceInterface
{
    /**
     * Create a new subscription transaction.
     * 
     * Persists the subscription transaction and updates account balance.
     * 
     * @param TransactionData $data Subscription transaction data
     * @return Transaction Created subscription transaction
     */
    public function create(TransactionData $data): Transaction;

    /**
     * Get active recurring transactions.
     *
     * Returns all transactions with 'is_subscription' true,
     * ordered by nearest payment date.
     *
     * @return \Illuminate\Database\Eloquent\Collection Active recurring transactions
     */
    public function getActiveSubscriptions(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Create a new transaction from a subscription.
     * 
     * Processes payment and updates the next payment date.
     * 
     * @param Transaction $subscription Subscription transaction
     * @return Transaction Created transaction
     */
    public function createFromSubscription(Transaction $subscription): Transaction;

    /**
     * End a subscription.
     *
     * Sets the 'is_subscription' flag to false.
     *
     * @param Transaction $subscription Subscription to end
     */
    public function endSubscription(Transaction $subscription): void;

    /**
     * Calculate the next payment date.
     * 
     * Determines the next payment date based on subscription period.
     * 
     * @param Transaction|TransactionData $transaction Subscription transaction or data
     * @return Carbon Next payment date
     */
    public function calculateNextPaymentDate(Transaction|TransactionData $transaction): Carbon;
} 