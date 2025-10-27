<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\Models\Transaction;
use App\Services\Transaction\Contracts\TransactionServiceInterface;
use App\DTOs\Transaction\TransactionData;
use Illuminate\Support\Facades\DB;
use App\Services\Transaction\Contracts\ExpenseTransactionServiceInterface;
use App\Services\Transaction\Contracts\IncomeTransactionServiceInterface;
use App\Services\Transaction\Contracts\InstallmentTransactionServiceInterface;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use App\Services\Transaction\Contracts\TransferTransactionServiceInterface;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Enums\TransactionTypeEnum;

/**
 * Transaction service
 * 
 * Manages all transactions for income/expense tracking.
 * Delegates to specialized services based on transaction type.
 */
final class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private readonly IncomeTransactionServiceInterface $incomeService,
        private readonly ExpenseTransactionServiceInterface $expenseService,
        private readonly TransferTransactionServiceInterface $transferService,
        private readonly InstallmentTransactionServiceInterface $installmentService,
        private readonly SubscriptionTransactionServiceInterface $subscriptionService,
        private readonly AccountBalanceServiceInterface $accountBalanceService
    ) {
    }

    /**
     * Create a new transaction.
     * 
     * Uses the appropriate service based on the transaction type.
     * 
     * @param TransactionData $data Transaction data
     * @return Transaction Created transaction
     * @throws \InvalidArgumentException When the transaction type is invalid
     */
    public function create(TransactionData $data): Transaction
    {
        return match ($data->type) {
            TransactionTypeEnum::INCOME->value => $this->incomeService->create($data),
            TransactionTypeEnum::EXPENSE->value => $this->expenseService->create($data),
            TransactionTypeEnum::TRANSFER->value => $this->transferService->create($data),
            TransactionTypeEnum::INSTALLMENT->value => $this->installmentService->create($data),
            TransactionTypeEnum::SUBSCRIPTION->value => $this->subscriptionService->create($data),
            default => throw new \InvalidArgumentException('Geçersiz عملية tipi: ' . $data->type),
        };
    }

    /**
     * Update the transaction.
     * 
     * First reverts the old transaction's balance effects, then updates the
     * transaction and applies the new transaction's balance effects.
     * 
     * @param Transaction $transaction Transaction to update
     * @param TransactionData $data New transaction data
     * @return Transaction Updated transaction
     */
    public function update(Transaction $transaction, TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // 1) Revert the old transaction's balance effects
            // revertTransaction now retrieves original values internally.
            $this->accountBalanceService->revertTransaction($transaction);

            // 2) Update the transaction
            // Note: If changing the transaction type is not supported,
            // add a check here. Currently the type can be updated.
            $transaction->update($data->toArray());

            // 3) Apply the balance effects for the updated transaction
            // Use the updated transaction instance
            match ($transaction->type) { 
                TransactionTypeEnum::INCOME->value => $this->accountBalanceService->updateForIncome($transaction),
                TransactionTypeEnum::EXPENSE->value => $this->accountBalanceService->updateForExpense($transaction),
                TransactionTypeEnum::TRANSFER->value => $this->accountBalanceService->updateForTransfer($transaction),
                TransactionTypeEnum::INSTALLMENT->value => $this->accountBalanceService->updateForInstallment($transaction),
                TransactionTypeEnum::SUBSCRIPTION->value => $this->accountBalanceService->updateForExpense($transaction), // Subscriptions behave like expenses for balance
                TransactionTypeEnum::LOAN_PAYMENT->value => $this->accountBalanceService->updateForLoanPayment($transaction),
                default => null,
            };

            return $transaction->fresh();
        });
    }

    /**
     * Delete the transaction.
     * 
     * Reverts related account balances before deleting the transaction.
     * 
     * @param Transaction $transaction Transaction to delete
     * @return bool True if the operation succeeded
     */
    public function delete(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            // revertTransaction reads original values internally, ensuring correctness.
            $this->accountBalanceService->revertTransaction($transaction);
            return $transaction->delete();
        });
    }
}