<?php

namespace App\Services\Account\Contracts;

use App\Models\Account;
use App\DTOs\Account\AccountData;

/**
 * Account service interface
 * 
 * Defines methods required to manage account operations.
 * Handles creating, updating, deleting, and other account-related operations.
 *
 * @return void
 */
interface AccountServiceInterface
{
    /**
     * Create a new account.
     * 
     * @param AccountData $data Account data
     * @return Account Created account
     */
    public function createAccount(AccountData $data): Account;

    /**
     * Update an existing account.
     * 
     * @param Account $account Account to update
     * @param AccountData $data New account data
     * @return Account Updated account
     */
    public function updateAccount(Account $account, AccountData $data): Account;

    /**
     * Delete an account.
     * 
     * @param Account $account Account to delete
     * @return bool True if successful, false otherwise
     */
    public function delete(Account $account): bool;

    /**
     * Create an installment purchase.
     * 
     * @param AccountData $data Installment purchase data
     * @return Transaction Created installment purchase transaction
     */
    public function createInstallmentPurchase(AccountData $data): \App\Models\Transaction;

    /**
     * Get installments for a credit card.
     * 
     * @param int $accountId Credit card account ID
     * @return \Illuminate\Database\Eloquent\Collection Installments
     */
    public function getInstallmentsForCard(int $accountId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Create a crypto wallet.
     * 
     * @param AccountData $data Crypto wallet data
     * @return Account Created crypto wallet
     */
    public function createCryptoWallet(AccountData $data): Account;

    /**
     * Create a virtual POS account.
     * 
     * @param AccountData $data Virtual POS data
     * @return Account Created virtual POS account
     */
    public function createVirtualPos(AccountData $data): Account;
}