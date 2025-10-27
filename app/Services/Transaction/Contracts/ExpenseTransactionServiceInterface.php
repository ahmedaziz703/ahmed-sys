<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Expense transactions service interface
 * 
 * Defines methods required to manage expense transactions.
 */
interface ExpenseTransactionServiceInterface
{
    /**
     * Create a new expense transaction.
     * 
     * Persists the transaction and updates related account balance.
     * 
     * @param TransactionData $data Expense transaction data
     * @return Transaction Created expense transaction
     */
    public function create(TransactionData $data): Transaction;
} 