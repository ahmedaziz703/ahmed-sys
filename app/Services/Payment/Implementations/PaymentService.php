<?php

declare(strict_types=1);

namespace App\Services\Payment\Implementations;

use App\Models\Account;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\Payment\Contracts\PaymentServiceInterface;

/**
 * Payment service implementation
 * 
 * Contains methods required to manage payment operations.
 * Handles payment processing, validation, status checks, and transfer between accounts.
 */
final class PaymentService implements PaymentServiceInterface
{
    /**
     * Process a payment.
     * 
     * @param mixed $entity Entity to pay (Debt, Loan, Account, Transaction)
     * @param array $data Payment data
     * @param string $paymentMethod Payment method
     */
    public function processPayment($entity, array $data, string $paymentMethod): void
    {
        DB::transaction(function () use ($entity, $data, $paymentMethod) {
            $amount = (float) $data['amount'];
            $sourceAccountId = $data['source_account_id'] ?? null;

            if ($sourceAccountId) {
                $sourceAccount = Account::findOrFail($sourceAccountId);
                if ($this->isDebit($entity)) {
                    $sourceAccount->balance -= $amount; 
                } else {
                    $sourceAccount->balance += $amount; 
                }
                $sourceAccount->save();
            }

            if ($entity instanceof Debt) {
                $entity->remaining_amount -= $amount;
                $entity->status = $entity->remaining_amount <= 0 ? 'paid' : $entity->status;
                $entity->save();
                $this->updateDebtStatus($entity);
            } elseif ($entity instanceof Loan) {
                $entity->remaining_amount -= $amount;
                $entity->status = $entity->remaining_amount <= 0 ? 'paid' : $entity->status;
                $entity->save();
                $this->updateLoanStatus($entity);
            } elseif ($entity instanceof Account && $entity->type === 'credit_card') {
                $this->updateCreditCardBalance($entity, $amount, $paymentMethod);
            } elseif ($entity instanceof Transaction) {
                $this->updateTransactionBalance($entity, $amount);
            }

            // Record the transaction
            $this->recordTransaction($entity, $amount, $paymentMethod, $sourceAccountId);
        });
    }

    /**
     * Update the debt status.
     * 
     * @param Debt $debt Debt to update
     */
    private function updateDebtStatus(Debt $debt): void
    {
        if ($debt->due_date && $debt->due_date < Carbon::now()->startOfDay() && $debt->status === 'pending') {
            $debt->update(['status' => 'overdue']);
        }
    }

    /**
     * Update the loan status.
     * 
     * @param Loan $loan Loan to update
     */
    private function updateLoanStatus(Loan $loan): void
    {
        if ($loan->due_date && $loan->due_date < Carbon::now()->startOfDay() && $loan->status === 'pending') {
            $loan->update(['status' => 'overdue']);
        }
    }

    /**
     * Update the credit card balance.
     * 
     * @param Account $card Credit card to update
     * @param float $amount Transaction amount
     * @param string $paymentMethod Payment method
     */
    private function updateCreditCardBalance(Account $card, float $amount, string $paymentMethod): void
    {
        $details = $card->details;
        if ($paymentMethod === 'payment') {
            $card->balance -= $amount; // When payment is made, the debt decreases
        } else {
            $card->balance += $amount; // When expense is made, the debt increases
        }
        $card->save();
    }

    /**
     * Update the transaction balance.
     * 
     * @param Transaction $transaction Transaction to update
     * @param float $amount Transaction amount
     */
    private function updateTransactionBalance(Transaction $transaction, float $amount): void
    {
        if ($transaction->source_account_id) {
            $sourceAccount = Account::findOrFail($transaction->source_account_id);
            $sourceAccount->balance -= $amount;
            $sourceAccount->save();
        }
        if ($transaction->destination_account_id) {
            $destinationAccount = Account::findOrFail($transaction->destination_account_id);
            $destinationAccount->balance += $amount;
            $destinationAccount->save();
        }
    }

    /**
     * Check if the entity is a debit.
     * 
     * @param mixed $entity Entity to check
     * @return bool Whether the entity is a debit
     */
    private function isDebit($entity): bool
    {
        return ($entity instanceof Debt && $entity->type === 'payable') ||
            ($entity instanceof Loan) ||
            ($entity instanceof Transaction && $entity->type === 'expense') ||
            ($entity instanceof Account && $entity->type === 'credit_card' && $entity->balance >= 0);
    }

    /**
     * Determine the transaction type.
     * 
     * @param mixed $entity Entity to determine the type
     * @return string Transaction type
     */
    private function getTransactionType($entity): string
    {
        if ($entity instanceof Transaction) {
            return match($entity->type) {
                'loan_payment' => 'expense',
                'debt_payment' => 'income',
                'income' => 'income',
                'expense' => 'expense',
                'transfer' => 'transfer',
                'payment' => 'expense',
                default => 'expense'
            };
        }
        return 'expense';
    }

    /**
     * Create a transaction record.
     * 
     * @param mixed $entity Entity to record
     * @param float $amount Transaction amount
     * @param string $paymentMethod Payment method
     * @param int|null $sourceAccountId Source account ID
     */
    private function recordTransaction($entity, float $amount, string $paymentMethod, ?int $sourceAccountId): void
    {
        $type = $this->getTransactionType($entity);
        $description = $entity->description ?? 'Finansal عملية';
        $categoryId = $entity->category_id ?? null;
        $destinationAccountId = $entity instanceof Account ? $entity->id : null;
        
        // Determine the installment number
        $installmentNumber = null;
        if ($entity instanceof Loan && str_contains($description, 'قسط')) {
            preg_match('/قسط (\d+)/', $description, $matches);
            $installmentNumber = $matches[1] ?? null;
        }

        Transaction::create([
            'user_id' => auth()->id(),
            'category_id' => $categoryId,
            'source_account_id' => $sourceAccountId,
            'destination_account_id' => $destinationAccountId,
            'type' => $type,
            'amount' => $amount,
            'currency' => $entity->currency ?? 'TRY',
            'exchange_rate' => $entity->exchange_rate ?? null,
            'try_equivalent' => $entity->currency !== 'TRY' ? ($amount * ($entity->exchange_rate ?? 1)) : $amount,
            'date' => now(),
            'payment_method' => $paymentMethod,
            'description' => $description,
            'reference_id' => $entity instanceof Loan ? $entity->id : ($entity instanceof Transaction ? $entity->id : null),
            'installment_number' => $installmentNumber,
            'status' => 'completed'
        ]);
    }

    /**
     * Transfer between accounts.
     * 
     * @param int $sourceAccountId Source account ID
     * @param int $targetAccountId Target account ID
     * @param float $amount Transfer amount
     * @param string|null $description Transfer description
     */
    public function transferBetweenAccounts(int $sourceAccountId, int $targetAccountId, float $amount, ?string $description = null): void
    {
        DB::transaction(function () use ($sourceAccountId, $targetAccountId, $amount, $description) {
            $sourceAccount = Account::findOrFail($sourceAccountId);
            $targetAccount = Account::findOrFail($targetAccountId);

            $exchangeRate = $sourceAccount->currency !== $targetAccount->currency ? ($data['exchange_rate'] ?? 1) : 1;
            $targetAmount = $amount * $exchangeRate;

            $sourceAccount->balance -= $amount;
            $targetAccount->balance += $targetAmount;

            $sourceAccount->save();
            $targetAccount->save();

            Transaction::create([
                'user_id' => auth()->id(),
                'source_account_id' => $sourceAccountId,
                'destination_account_id' => $targetAccountId,
                'type' => 'transfer',
                'amount' => $amount,
                'currency' => $sourceAccount->currency,
                'exchange_rate' => $exchangeRate,
                'try_equivalent' => $amount * $exchangeRate,
                'date' => now(),
                'description' => $description ?? "{$sourceAccount->name}'dan {$targetAccount->name}'a transfer",
            ]);
        });
    }

    /**
     * Validate payment data.
     * 
     * @param array $data Payment data to validate
     * @return bool Validity of the data
     */
    public function validatePayment(array $data): bool
    {
        // Validate payment data
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return false;
        }

        // Validate payment method
        if (empty($data['payment_method'])) {
            return false;
        }

        // Validate source account
        if (isset($data['source_account_id']) && !Account::find($data['source_account_id'])) {
            return false;
        }

        if (isset($data['destination_account_id']) && !Account::find($data['destination_account_id'])) {
            return false;
        }

        return true;
    }

    /**
     * Get payment status.
     * 
     * @param string $paymentId Payment ID
     * @return string Payment status
     */
    public function getPaymentStatus(string $paymentId): string
    {
        $transaction = Transaction::find($paymentId);
        
        if (!$transaction) {
            return 'not_found';
        }

        return $transaction->status;
    }
}