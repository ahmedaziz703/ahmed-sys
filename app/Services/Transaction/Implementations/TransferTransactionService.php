<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\TransferTransactionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Transfer transactions service
 * 
 * Manages money transfers between accounts.
 * Supports transfers across different currencies with exchange conversion.
 */
final class TransferTransactionService implements TransferTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Create a new transfer transaction.
     * 
     * Transfers funds between source and destination accounts.
     * Supports cross-currency transfers.
     * 
     * @param TransactionData $data Transfer data
     * @return Transaction Created transfer transaction
     * @throws \InvalidArgumentException When transfer data is invalid
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $this->validateTransferData($data);
            $rates = $this->calculateExchangeRates($data);
            $transaction = $this->createTransferRecord($data, $rates);
            $this->balanceService->updateForTransfer($transaction, $rates);
            return $transaction;
        });
    }

    /**
     * Calculate exchange rates.
     * 
     * Performs conversion when source and destination currencies differ.
     * 
     * @param TransactionData $data Transfer data
     * @return array Exchange rate info
     */
    private function calculateExchangeRates(TransactionData $data): array
    {
        if ($data->source_currency === $data->destination_currency) {
            return [];
        }

        // TODO: Implement exchange rate calculation
        return [
            $data->source_currency => [
                $data->destination_currency => 1.0
            ]
        ];
    }

    /**
     * Create the transfer record.
     * 
     * @param TransactionData $data Transfer data
     * @param array $rates Exchange rate info
     * @return Transaction Created transfer record
     */
    private function createTransferRecord(TransactionData $data, array $rates): Transaction
    {
        $description = $this->generateTransferDescription($data);
        
        return Transaction::create([
            ...$data->toArray(),
            'description' => $description,
            'exchange_rates' => $rates,
        ]);
    }

    /**
     * Generate a transfer description.
     * 
     * @param TransactionData $data Transfer data
     * @return string Generated description
     */
    private function generateTransferDescription(TransactionData $data): string
    {
        $sourceAccount = Account::findOrFail($data->source_account_id);
        $targetAccount = Account::findOrFail($data->destination_account_id);

        return sprintf(
            '%sتحويل من حساب %s إلى حساب',
            $sourceAccount->name,
            $targetAccount->name
        );
    }

    /**
     * Validate transfer data.
     * 
     * Ensures the existence of source and destination accounts and sufficient balance.
     * 
     * @param TransactionData $data Transfer data to validate
     * @throws \InvalidArgumentException When data is invalid
     */
    private function validateTransferData(TransactionData $data): void
    {
        if (!$data->source_account_id || !$data->destination_account_id) {
            throw new \InvalidArgumentException('مطلوب إدخال معلومات حساب المصدر والوجهة');
        }

        if ($data->source_account_id === $data->destination_account_id) {
            throw new \InvalidArgumentException('لا يمكن أن يكون حساب المصدر والوجهة نفس الحساب');
        }

        $sourceAccount = Account::findOrFail($data->source_account_id);
        if ($sourceAccount->balance < $data->amount) {
            throw new \InvalidArgumentException('الرصيد غير كافٍ');
        }
    }
} 