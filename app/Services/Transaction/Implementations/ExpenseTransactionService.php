<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\ExpenseTransactionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Expense transactions service
 * 
 * Manages expense transactions.
 * Handles recording expenses and updating account balances.
 */
final class ExpenseTransactionService implements ExpenseTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Create a new expense transaction.
     * 
     * Persists the transaction and updates the related account balance.
     * Applies special logic for credit card transactions.
     * 
     * @param TransactionData $data Expense transaction data
     * @return Transaction Created expense transaction
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                ...$data->toArray(),
                'type' => 'expense',
            ]);
            
            $this->balanceService->updateForExpense($transaction);
            return $transaction;
        });
    }
} 