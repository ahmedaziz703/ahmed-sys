<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Currency\CurrencyConversionService;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Account balance service
 * 
 * Updates account balances after transactions.
 * Handles balance updates for income, expense, transfer, and installment transactions.
 */
final class AccountBalanceService implements AccountBalanceServiceInterface
{
    private CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Update balance for an income transaction.
     * 
     * Increases the destination account's balance.
     * 
     * @param Transaction $transaction Income transaction to process
     */
    public function updateForIncome(Transaction $transaction): void
    {
        if ($transaction->destinationAccount) {
            $this->updateBalance(
                $transaction->destinationAccount,
                (float) $transaction->amount,
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }
    }

    /**
     * Update balance for an expense transaction.
     * 
     * Decreases the source account's balance.
     * Applies special logic for credit card transactions.
     * 
     * @param Transaction $transaction Expense transaction to process
     */
    public function updateForExpense(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            $amount = 0.0;
            // If paid by credit card, the debt increases
            if ($transaction->sourceAccount->type === Account::TYPE_CREDIT_CARD) {
                $amount = (float) $transaction->amount; // Debt increases (positive)
            } else {
                // If paid by a normal account, the balance decreases
                $amount = -(float) $transaction->amount; // Balance decreases (negative)
            }

            if ($amount != 0) {
                $this->updateBalance(
                    $transaction->sourceAccount,
                    $amount,
                    $transaction->currency,
                    Carbon::parse($transaction->date)
                );
            }
        }
    }

    /**
     * Update balances for a transfer transaction.
     * 
     * Decreases the source account and increases the destination account.
     * Supports cross-currency transfers.
     * 
     * @param Transaction $transaction Transfer transaction to process
     */
    public function updateForTransfer(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            $this->updateBalance(
                $transaction->sourceAccount,
                -$transaction->amount,
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }

        if ($transaction->destinationAccount) {
            $this->updateBalance(
                $transaction->destinationAccount,
                $transaction->amount,
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }
    }

    /**
     * Update balance for an installment transaction.
     * 
     * Updates the balance (current implementation decreases the balance).
     * 
     * @param Transaction $transaction Installment transaction to process
     */
    public function updateForInstallment(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            // According to the current implementation, the balance decreases.
            $this->updateBalance(
                $transaction->sourceAccount,
                -(float) $transaction->amount, 
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }
    }

    /**
     * Update balance for a loan payment transaction.
     * If paid via credit card, the debt increases.
     */
    public function updateForLoanPayment(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            $amount = 0.0;
            // If credit card, debt INCREASES (+ amount)
            if ($transaction->sourceAccount->type === Account::TYPE_CREDIT_CARD) {
                $amount = (float) $transaction->amount;
            } else {
                // If a normal account, balance DECREASES (- amount)
                $amount = -(float) $transaction->amount;
            }
            if ($amount != 0) {
                $this->updateBalance(
                    $transaction->sourceAccount,
                    $amount,
                    $transaction->currency,
                    Carbon::parse($transaction->date)
                );
            }
        }
    }

    /**
     * Revert a transaction.
     * 
     * Restores account balances based on the original transaction type.
     * Uses original transaction details to perform the revert.
     * 
     * @param Transaction $transaction Transaction to revert
     */
    public function revertTransaction(Transaction $transaction): void
    {
        $originalType = $transaction->getOriginal('type');
        $originalSourceAccountId = $transaction->getOriginal('source_account_id');
        $originalDestinationAccountId = $transaction->getOriginal('destination_account_id');
        $originalAmount = (float) $transaction->getOriginal('amount');
        $originalCurrency = (string) $transaction->getOriginal('currency');
        $originalDate = Carbon::parse($transaction->getOriginal('date'));

        if ($originalAmount <= 0) { return; }

        match ($originalType) {
            TransactionTypeEnum::EXPENSE->value => $this->revertExpense($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::INCOME->value => $this->revertIncome($originalDestinationAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::TRANSFER->value => $this->revertTransfer($originalSourceAccountId, $originalDestinationAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::INSTALLMENT->value => $this->revertInstallment($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::SUBSCRIPTION->value => $this->revertSubscription($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::LOAN_PAYMENT->value => $this->revertLoanPayment($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            default => null,
        };
    }

    /**
     * Revert an expense transaction.
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertExpense(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $revertAmount = 0.0;
                // If a credit card transaction, decrease debt (negative)
                if ($oldSourceAccount->type === Account::TYPE_CREDIT_CARD) {
                    $revertAmount = -$originalAmount; 
                } else {
                    // If a normal account, increase balance (positive)
                    $revertAmount = $originalAmount;
                }

                if ($revertAmount != 0) {
                    $this->updateBalance(
                        $oldSourceAccount,
                        $revertAmount,
                        $originalCurrency,
                        $originalDate
                    );
                }
            }
        }
    }

    /**
     * Revert an income transaction.
     *
     * @param int|null $oldDestinationAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertIncome(?int $oldDestinationAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldDestinationAccountId) {
            $oldDestinationAccount = Account::find($oldDestinationAccountId);
            if ($oldDestinationAccount) {
                $revertAmount = -$originalAmount;

                $this->updateBalance(
                    $oldDestinationAccount,
                    $revertAmount,
                    $originalCurrency,
                    $originalDate
                );
            }
        }
    }

    /**
     * Revert a transfer transaction.
     *
     * @param int|null $oldSourceAccountId
     * @param int|null $oldDestinationAccountId
     * @param float $originalAmount
     * @param string $originalCurrency Currency of the original amount
     * @param Carbon $originalDate
     */
    private function revertTransfer(?int $oldSourceAccountId, ?int $oldDestinationAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        // Revert source account
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $this->updateBalance(
                    $oldSourceAccount,
                    $originalAmount, 
                    $originalCurrency, 
                    $originalDate
                );
            }
        }

        // Revert destination account
        if ($oldDestinationAccountId) {
            $oldDestinationAccount = Account::find($oldDestinationAccountId);
            if ($oldDestinationAccount) {
                $this->updateBalance(
                    $oldDestinationAccount,
                    -$originalAmount, 
                    $originalCurrency, 
                    $originalDate
                );
            }
        }
    }

    /**
     * Revert an installment transaction.
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertInstallment(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $revertAmount = $originalAmount;

                $this->updateBalance(
                    $oldSourceAccount,
                    $revertAmount, 
                    $originalCurrency,
                    $originalDate
                );
            }
        }
    }

    /**
     * Revert a subscription transaction (behaves like an expense).
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertSubscription(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        $this->revertExpense($oldSourceAccountId, $originalAmount, $originalCurrency, $originalDate);
    }

    /**
     * Revert a loan payment transaction.
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertLoanPayment(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $revertAmount = 0.0;
                // If a credit card transaction, decrease debt (negative)
                if ($oldSourceAccount->type === Account::TYPE_CREDIT_CARD) {
                    $revertAmount = -$originalAmount; 
                } else {
                    // If a normal account, increase balance (positive)
                    $revertAmount = $originalAmount;
                }

                if ($revertAmount != 0) {
                    $this->updateBalance(
                        $oldSourceAccount,
                        $revertAmount,
                        $originalCurrency,
                        $originalDate
                    );
                }
            }
        }
    }

    /**
     * Update the account balance and perform currency conversion.
     *
     * @param Account $account Account to update
     * @param float $amount Amount to add/subtract (sign matters)
     * @param string $transactionCurrency Currency of the transaction
     * @param Carbon $date Transaction date (for conversion)
     */
    private function updateBalance(Account $account, float $amount, string $transactionCurrency, Carbon $date): void
    {
        if ($amount == 0) { 
            return;
        }

        $adjustmentAmount = 0.0;

        // If account currency equals transaction currency, use the amount directly
        if ($account->currency === $transactionCurrency) {
            $adjustmentAmount = $amount;
        } else {
            // Otherwise convert between currencies (preserving sign)
            $convertedAmount = $this->currencyService->convert(
                $amount,
                $transactionCurrency,
                $account->currency,
                $date
            );
            $adjustmentAmount = (float) $convertedAmount;
        }

        // Apply the adjustment amount to the balance
        $account->balance += $adjustmentAmount;

        // Round to 2 decimals and save
        $account->balance = round($account->balance, 2);
        $account->save();
    }

    /**
     * Check whether the account has enough balance.
     * 
     * @param Account $account Account to check
     * @param float $amount Required amount
     * @param string $currency Transaction currency
     * @return bool True if there is sufficient balance
     */
    public function hasEnoughBalance(Account $account, float $amount, string $currency): bool
    {
        // If account currency equals transaction currency, compare directly
        if ($account->currency === $currency) {
            return $account->balance >= $amount;
        }

        // For different currencies, convert to TRY for comparison
        $accountBalanceInTRY = $this->currencyService->convertToTRY($account->balance, $account->currency);
        $amountInTRY = $this->currencyService->convertToTRY($amount, $currency);

        // Round both amounts to 2 decimals
        $accountBalanceInTRY = round($accountBalanceInTRY, 2);
        $amountInTRY = round($amountInTRY, 2);

        return $accountBalanceInTRY >= $amountInTRY;
    }

    /**
     * Get the account's current balance in the requested currency.
     * 
     * @param Account $account Account
     * @param string $currency Desired currency
     * @return float Account balance
     */
    public function getAvailableBalance(Account $account, string $currency): float
    {
        // If account currency equals transaction currency, return balance directly
        if ($account->currency === $currency) {
            return round($account->balance, 2);
        }

        // For different currencies, convert to TRY
        $balanceInTRY = $this->currencyService->convertToTRY($account->balance, $account->currency);
        return round($balanceInTRY, 2);
    }
} 