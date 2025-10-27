<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Subscription transactions service
 * 
 * Manages subscription transactions.
 * Tracks subscription payments and renewals.
 */
final class SubscriptionTransactionService implements SubscriptionTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Create a new subscription transaction.
     * 
     * Persists the subscription transaction and updates related account balance.
     * 
     * @param TransactionData $data Subscription transaction data
     * @return Transaction Created subscription transaction
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                ...$data->toArray(),
                'is_subscription' => true,
                'next_payment_date' => $this->calculateNextPaymentDate($data),
            ]);
            
            $this->balanceService->updateForExpense($transaction);
            return $transaction;
        });
    }

    /**
     * Create a new transaction from a subscription.
     * 
     * Processes the subscription payment and updates the next payment date.
     * 
     * @param Transaction $subscription Subscription transaction
     * @return Transaction Newly created transaction
     */
    public function createFromSubscription(Transaction $subscription): Transaction
    {
        return DB::transaction(function () use ($subscription) {
            $data = TransactionData::fromArray([
                'user_id' => $subscription->user_id,
                'category_id' => $subscription->category_id,
                'source_account_id' => $subscription->source_account_id,
                'destination_account_id' => $subscription->destination_account_id,
                'customer_id' => $subscription->customer_id,
                'supplier_id' => $subscription->supplier_id,
                'type' => $subscription->type,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'exchange_rate' => $subscription->exchange_rate,
                'try_equivalent' => $subscription->try_equivalent,
                'date' => now()->toDateString(),
                'payment_method' => $subscription->payment_method,
                'description' => $subscription->description,
                'is_subscription' => false, 
                'status' => 'completed',
            ]);

            $newTransaction = Transaction::create($data->toArray());
            
            // Update next payment date
            $subscription->next_payment_date = $this->calculateNextPaymentDate($subscription);
            $subscription->save();

            return $newTransaction;
        });
    }

    /**
     * End the subscription.
     *
     * Sets the transaction's 'is_subscription' flag to false.
     *
     * @param Transaction $subscription Subscription to end
     */
    public function endSubscription(Transaction $subscription): void
    {
        $subscription->is_subscription = false;
        $subscription->save();
    }

    /**
     * Get active recurring transactions.
     *
     * Returns all transactions where 'is_subscription' is true,
     * ordered by the next payment date.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSubscriptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('is_subscription', true)
            ->orderBy('next_payment_date')
            ->get();
    }

    /**
     * Calculate the next payment date.
     * 
     * @param Transaction|TransactionData $transaction Subscription transaction
     * @return Carbon Next payment date
     */
    public function calculateNextPaymentDate(Transaction|TransactionData $transaction): Carbon
    {
        // Determine the base date: if a Transaction model, use its current next_payment_date
        // or transaction date; if TransactionData, use its date.
        $currentDate = $transaction instanceof Transaction
            ? Carbon::parse($transaction->next_payment_date ?? $transaction->date)
            : Carbon::parse($transaction->date);

        // Advance the date based on the subscription period
        return match ($transaction->subscription_period) {
            'daily' => $currentDate->addDay(), // Daily
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(), // addMonth() used
            'quarterly' => $currentDate->addMonths(3),
            'biannually' => $currentDate->addMonths(6),
            'annually' => $currentDate->addYear(), // addYear() used
            default => $currentDate->addMonth(), // Default to 1 month (safer)
        };
    }
} 