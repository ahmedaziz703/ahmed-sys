<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Installment transactions service interface
 * 
 * Defines methods to manage installment transactions,
 * including creation and overall management.
 */
interface InstallmentTransactionServiceInterface
{
    /**
     * Create a new installment transaction.
     * 
     * Persists the transaction and updates account balance.
     * 
     * @param TransactionData $data Installment transaction data
     * @return Transaction Created installment transaction
     */
    public function create(TransactionData $data): Transaction;
} 