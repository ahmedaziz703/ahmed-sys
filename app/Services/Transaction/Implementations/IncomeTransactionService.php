<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\IncomeTransactionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Income transactions service
 * 
 * Manages income transactions.
 * Handles recording income and updating account balances.
 */
final class IncomeTransactionService implements IncomeTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Create a new income transaction.
     * 
     * Persists the transaction and updates the related account balance.
     * 
     * @param TransactionData $data Income transaction data
     * @return Transaction Created income transaction
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                ...$data->toArray(),
                'type' => 'income',
            ]);
            
            $this->balanceService->updateForIncome($transaction);
            return $transaction;
        });
    }
} 