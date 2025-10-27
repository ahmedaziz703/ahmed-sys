<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\Models\Transaction;

/**
 * Account balance service interface
 * 
 * Defines methods for updating and managing account balances.
 * Handles balance updates for different transaction types.
 */
interface AccountBalanceServiceInterface
{
    /**
     * Update account balance for an income transaction.
     * 
     * @param Transaction $transaction Income transaction to process
     */
    public function updateForIncome(Transaction $transaction): void;

    /**
     * Update account balance for an expense transaction.
     * 
     * @param Transaction $transaction Expense transaction to process
     */
    public function updateForExpense(Transaction $transaction): void;

    /**
     * Update account balances for a transfer transaction.
     * 
     * @param Transaction $transaction Transfer transaction to process
     */
    public function updateForTransfer(Transaction $transaction): void;

    /**
     * Update account balance for an installment transaction.
     * 
     * @param Transaction $transaction Installment transaction to process
     */
    public function updateForInstallment(Transaction $transaction): void;

    /**
     * Update account balance for a loan payment transaction.
     * 
     * @param Transaction $transaction Loan payment transaction to process
     */
    public function updateForLoanPayment(Transaction $transaction): void;

    /**
     * Revert a transaction.
     * 
     * Restores account balances based on the transaction type.
     * 
     * @param Transaction $transaction Transaction to revert
     */
    public function revertTransaction(Transaction $transaction): void;
} 