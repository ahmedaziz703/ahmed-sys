<?php

declare(strict_types=1);

namespace App\Services\Account\Implementations;

use App\Models\Account;
use App\Models\Transaction;
use App\DTOs\Account\AccountData;
use App\Services\Account\Contracts\AccountServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\PaymentMethodEnum;
use Filament\Notifications\Notification;

/**
 * Account service implementation
 * 
 * Contains methods required to manage account operations.
 * Handles creating, updating, deleting, and other account-related operations.
 */
class AccountService implements AccountServiceInterface
{
    /**
     * Create a new account.
     * 
     * @param AccountData $data Account data
     * @return Account Created account
     */
    public function createAccount(AccountData $data): Account
    {
        return DB::transaction(function () use ($data) {
            $account = new Account();
            $account->user_id = $data->user_id ?? auth()->id();
            $account->name = $data->name;
            $account->type = $data->type;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? 0;
            $account->details = $this->prepareDetails($data);
            $account->status = $data->status ?? true;
            $account->save();

            return $account;
        });
    }

    /**
     * Update an existing account.
     * 
     * @param Account $account Account to update
     * @param AccountData $data New account data
     * @return Account Updated account
     */
    public function updateAccount(Account $account, AccountData $data): Account
    {
        return DB::transaction(function () use ($account, $data) {
            $account->name = $data->name;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? $account->balance;
            $account->details = $this->prepareDetails($data);
            $account->status = $data->status ?? $account->status;
            $account->save();

            return $account;
        });
    }

    /**
     * Delete the account.
     * 
     * @param Account $account Account to delete
     * @return bool True on success, false otherwise
     */
    public function delete(Account $account): bool
    {
        try {
            // Transaction check
            if ($account->sourceTransactions()->withTrashed()->exists() || $account->destinationTransactions()->withTrashed()->exists()) {
                $accountType = match($account->type) {
                    Account::TYPE_CRYPTO_WALLET => 'محفظة العملات المشفرة',
                    Account::TYPE_VIRTUAL_POS => 'نقاط البيع الافتراضية',
                    Account::TYPE_BANK_ACCOUNT => 'حساب مصرفي',
                    Account::TYPE_CREDIT_CARD => 'بطاقة ائتمان',
                    default => 'حساب'
                };

                throw new \Exception(
                    "يرجى الغاء {$accountType}  التفعيل "
                );
            }

            return $account->delete();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('حساب مفعل!')
                ->body($e->getMessage())
                ->duration(10000)
                ->send();
            
            return false;
        }
    }

    /**
     * Prepare account details.
     * 
     * Builds required details based on the account type.
     * 
     * @param AccountData $data Account data
     * @return array Prepared account details
     */
    private function prepareDetails(AccountData $data): array
    {
        $details = $data->details ?? [];
        return match ($data->type) {
            Account::TYPE_BANK_ACCOUNT => [
                'bank_name' => $details['bank_name'] ?? null,
                'account_number' => $details['account_number'] ?? null,
                'iban' => $details['iban'] ?? null,
                'branch_code' => $details['branch_code'] ?? null,
                'branch_name' => $details['branch_name'] ?? null,
            ],
            Account::TYPE_CREDIT_CARD => [
                'bank_name' => $details['bank_name'] ?? null,
                'credit_limit' => $details['credit_limit'] ?? null,
                'statement_day' => $details['statement_day'] ?? null,
                'current_debt' => $details['current_debt'] ?? 0,
            ],
            Account::TYPE_CRYPTO_WALLET => [
                'platform' => $details['platform'] ?? null,
                'wallet_address' => $details['wallet_address'] ?? null,
            ],
            Account::TYPE_VIRTUAL_POS => [
                'provider' => $details['provider'] ?? null
            ],
            default => [],
        };
    }

    /**
     * Create an installment purchase transaction.
     * 
     * @param AccountData $data Installment purchase data
     * @return Transaction Created installment purchase transaction
     */
    public function createInstallmentPurchase(AccountData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = Account::where('type', Account::TYPE_CREDIT_CARD)
                ->where('id', $data->account_id)
                ->firstOrFail();

            $details = $account->details;
            $availableLimit = ($details['credit_limit'] ?? 0) - $account->balance;
            if ($data->amount > $availableLimit) {
                throw new \Exception('بطاقة ائتمانnda yeterli limit bulunmuyor.');
            }

            $transaction = new Transaction();
            $transaction->user_id = auth()->id();
            $transaction->source_account_id = $account->id;
            $transaction->type = Transaction::TYPE_EXPENSE;
            $transaction->amount = $data->amount;
            $transaction->currency = $account->currency;
            $transaction->exchange_rate = $data->exchange_rate ?? null;
            $transaction->try_equivalent = $data->amount * ($data->exchange_rate ?? 1);
            $transaction->date = $data->transaction_date;
            $transaction->description = $data->description;
            $transaction->category_id = $data->category_id;
            $transaction->supplier_id = $data->supplier_id ?? null;
            $transaction->installments = $data->installments ?? 1;
            $transaction->remaining_installments = $data->installments ?? 1;
            $transaction->monthly_amount = $data->amount / ($data->installments ?? 1);
            $transaction->next_payment_date = $data->next_payment_date ?? null;
            $transaction->save();

            $account->balance += $data->amount;
            $account->save();

            return $transaction;
        });
    }

    /**
     * Get installment payments for a credit card account.
     * 
     * @param int $accountId Credit card account ID
     * @return \Illuminate\Database\Eloquent\Collection Installment payments
     */
    public function getInstallmentsForCard(int $accountId): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('source_account_id', $accountId)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('installments', '>', 1)
            ->where('remaining_installments', '>', 0)
            ->orderBy('date')
            ->get();
    }

    /**
     * Update account balance.
     * 
     * @param int $accountId Account ID
     * @param float $amount Transaction amount
     * @param string $currency Currency
     * @param string $operation Operation type (add/subtract)
     */
    public function updateAccountBalance(int $accountId, float $amount, string $currency, string $operation): void
    {
        $account = Account::findOrFail($accountId);
        DB::transaction(function () use ($account, $amount, $currency, $operation) {
            $adjustedAmount = $amount;
            if ($account->currency !== $currency) {
                $adjustedAmount = $amount * ($account->exchange_rate ?? 1);
            }

            switch ($account->type) {
                case Account::TYPE_CREDIT_CARD:
                    // Credit card: Expense -> debt increases, Payment -> debt decreases
                    if ($operation === 'add') {
                        $account->balance -= $adjustedAmount;
                    } else {
                        $account->balance += $adjustedAmount;
                    }
                    break;

                case Account::TYPE_BANK_ACCOUNT:
                case Account::TYPE_CRYPTO_WALLET:
                case Account::TYPE_VIRTUAL_POS:
                case Account::TYPE_CASH:
                    // Regular accounts: Income -> balance increases, Expense -> balance decreases
                    if ($operation === 'add') {
                        $account->balance += $adjustedAmount;
                    } else {
                        $account->balance -= $adjustedAmount;
                    }
                    break;
            }

            $account->save();
        });
    }

    /**
     * Create a crypto wallet account.
     * 
     * @param AccountData $data Crypto wallet data
     * @return Account Created crypto wallet account
     */
    public function createCryptoWallet(AccountData $data): Account
    {


        return DB::transaction(function () use ($data) {
            $account = new Account();
            
            // Set user_id directly
            $account->user_id = auth()->id() ?? $data->user_id;
            
            if (!$account->user_id) {
                throw new \Exception('User ID is required!');
            }

            $account->name = $data->name;
            $account->type = Account::TYPE_CRYPTO_WALLET;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? 0;
            $account->details = [
                'platform' => $data->details['platform'] ?? null,
                'wallet_address' => $data->details['wallet_address'] ?? null,
            ];
            $account->status = $data->status ?? true;

            Log::info('Before Save:', [
                'account_data' => $account->toArray()
            ]);

            $account->save();
            return $account;
        });
    }

    /**
     * Create a virtual POS account.
     * 
     * @param AccountData $data Virtual POS data
     * @return Account Created virtual POS account
     */
    public function createVirtualPos(AccountData $data): Account
    {
        return DB::transaction(function () use ($data) {
            $account = new Account();
            $account->user_id = $data->user_id ?? auth()->id();
            $account->name = $data->name;
            $account->type = Account::TYPE_VIRTUAL_POS;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? 0;
            $account->details = [
                'provider' => $data->details['provider'] ?? null,
                'merchant_id' => $data->details['merchant_id'] ?? null,
                'terminal_id' => $data->details['terminal_id'] ?? null,
            ];
            $account->status = $data->status ?? true;
            $account->save();

            return $account;
        });
    }

    /**
     * Make a credit card payment.
     * 
     * @param int $creditCardId Credit card account ID
     * @param float $amount Payment amount
     * @param string $paymentMethod Payment method
     * @param int|null $sourceAccountId Source account ID
     * @param string|null $date Transaction date
     */
    public function makeCardPayment(
        int $creditCardId, 
        float $amount, 
        string $paymentMethod,
        ?int $sourceAccountId = null,
        ?string $date = null
    ) {
        try {
            DB::transaction(function () use ($creditCardId, $amount, $paymentMethod, $sourceAccountId, $date) {
                $creditCard = Account::findOrFail($creditCardId);
                
                // Get current debt amount
                $currentDebt = $creditCard->balance;
                
                // If payment amount exceeds the debt
                if ($amount > $currentDebt) {
                    // Calculate overpayment amount
                    $overpayment = $amount - $currentDebt;
                    
                    // Send a notification to the user
                    Notification::make()
                        ->warning()
                        ->title('تحذير الدفع الزائد')
                        ->body("دين بطاقة ائتمان الحالي {$currentDebt} {$creditCard->currency}، لكنك تريد دفع {$amount} {$creditCard->currency}. تم تقليل مبلغ الدفع إلى مقدار الدين الحالي.")
                        ->duration(8000)
                        ->send();
                    
                    // Limit the payment amount to the current debt
                    $amount = $currentDebt;
                }
                
                // Reduce credit card debt
                $creditCard->balance = max(0, $creditCard->balance - $amount);
                $creditCard->save();

                // If paid from a bank account, the amount deducted from the bank
                // account should reflect the limited amount in overpayment cases
                if ($paymentMethod === PaymentMethodEnum::BANK->value && $sourceAccountId) {
                    $sourceAccount = Account::findOrFail($sourceAccountId);
                    $sourceAccount->balance -= $amount; // Using the limited amount now
                    $sourceAccount->save();
                }

                // Record the payment transaction
                $transaction = new Transaction();
                $transaction->user_id = auth()->id();
                $transaction->source_account_id = $sourceAccountId;
                $transaction->destination_account_id = $creditCard->id;
                $transaction->type = Transaction::TYPE_CREDIT_PAYMENT;
                $transaction->amount = $amount;
                $transaction->currency = $creditCard->currency;
                $transaction->date = $date ?? now();
                $transaction->payment_method = $paymentMethod;
                $transaction->description = 'بطاقة ائتمان ödemesi';
                $transaction->save();
                
                // Send success notification
                Notification::make()
                    ->success()
                    ->title('تم الدفع بنجاح')
                    ->body("تم إجراء دفعة بطاقة ائتمان بقيمة {$amount} {$creditCard->currency} بنجاح.")
                    ->duration(5000)
                    ->send();
            });
        } catch (\Exception $e) {
            throw new \Exception('بطاقة ائتمان ödemesi yapılırken bir hata oluştu: ' . $e->getMessage());
        }
    }
}