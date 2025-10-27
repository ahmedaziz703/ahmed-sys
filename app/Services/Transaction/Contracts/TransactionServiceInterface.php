<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;

/**
 * Transaction service interface
 * 
 * Defines core transaction operations and delegates to specialized
 * services for each transaction type.
 */
interface TransactionServiceInterface
{
    /**
     * Create a new transaction.
     * 
     * Uses the appropriate service based on the transaction type.
     * 
     * @param TransactionData $data Transaction data
     * @return Transaction Created transaction
     * @throws \InvalidArgumentException When the transaction type is invalid
     */
    public function create(TransactionData $data): Transaction;

    /**
     * Update the transaction.
     * 
     * Updates transaction data and adjusts related account balances.
     * 
     * @param Transaction $transaction Transaction to update
     * @param TransactionData $data New transaction data
     * @return Transaction Updated transaction
     */
    public function update(Transaction $transaction, TransactionData $data): Transaction;

    /**
     * Delete the transaction.
     * 
     * Reverts related account balances before deletion.
     * 
     * @param Transaction $transaction Transaction to delete
     * @return bool True if the operation succeeded
     */
    public function delete(Transaction $transaction): bool;
}