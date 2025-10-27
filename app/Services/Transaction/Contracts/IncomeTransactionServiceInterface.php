<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Income transactions service interface
 * 
 * Defines methods required to manage income transactions.
 */
interface IncomeTransactionServiceInterface
{
    /**
     * Create a new income transaction.
     * 
     * Persists the transaction and updates related account balance.
     * 
     * @param TransactionData $data Income transaction data
     * @return Transaction Created income transaction
     */
    public function create(TransactionData $data): Transaction;
} 