<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\InstallmentTransactionServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Installment transactions service
 * 
 * Manages installment payments made by credit card.
 * Tracks number of installments, monthly amount, and payment dates.
 */
final class InstallmentTransactionService implements InstallmentTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Create a new installment transaction.
     * 
     * Validates credit card limit and persists installment details.
     * 
     * @param TransactionData $data Installment data
     * @return Transaction Created installment transaction
     * @throws \InvalidArgumentException When data is invalid
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $this->validateInstallmentData($data);
            $transaction = $this->createInstallmentRecord($data);
            $this->balanceService->updateForInstallment($transaction);
            return $transaction;
        });
    }

    /**
     * Process an installment payment.
     * 
     * Decrements remaining installments and sets the next payment date.
     * 
     * @param Transaction $transaction Installment transaction to process
     */
    public function processInstallmentPayment(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Update remaining installments
            $transaction->remaining_installments--;
            
            // Update next payment date
            if ($transaction->remaining_installments > 0) {
                $transaction->next_payment_date = Carbon::parse($transaction->next_payment_date)->addMonth();
            }
            
            $transaction->save();
        });
    }

    /**
     * Validate installment payment data.
     * 
     * Ensures the source account exists and the credit limit is sufficient.
     * 
     * @param TransactionData $data Installment data to validate
     * @throws \InvalidArgumentException When data is invalid
     */
    private function validateInstallmentData(TransactionData $data): void
    {
        if (!$data->source_account_id) {
            throw new \InvalidArgumentException('Kaynak حساب bilgisi gereklidir.');
        }

        $account = Account::findOrFail($data->source_account_id);
        if ($account->type !== Account::TYPE_CREDIT_CARD) {
            throw new \InvalidArgumentException('الدفع بالتقسيط متاح فقط لبطاقات الائتمان.');
        }

        if ($account->balance + $data->amount > $account->limit) {
            throw new \InvalidArgumentException('بطاقة ائتمان limiti aşıldı.');
        }
    }

    /**
     * Create an installment transaction record.
     * 
     * @param TransactionData $data Installment data
     * @return Transaction Created installment record
     */
    private function createInstallmentRecord(TransactionData $data): Transaction
    {
        $description = sprintf(
            'دفع بالتقسيط - %d/%d',
            $data->installment_number,
            $data->total_installments
        );

        return Transaction::create([
            ...$data->toArray(),
            'description' => $description,
        ]);
    }
} 