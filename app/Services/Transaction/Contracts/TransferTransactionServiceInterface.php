<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Transfer transactions service interface
 * 
 * Defines methods required to manage transfers between accounts.
 */
interface TransferTransactionServiceInterface
{
    /**
     * Create a new transfer transaction.
     * 
     * Persists the transfer and updates related account balances.
     * 
     * @param TransactionData $data Transfer transaction data
     * @return Transaction Created transfer transaction
     */
    public function create(TransactionData $data): Transaction;
} 