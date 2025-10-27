<?php

namespace App\Livewire\Transaction;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;
use App\Services\Transaction\Implementations\TransactionService;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use App\Services\Currency\CurrencyService;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Enums\PaymentMethodEnum;
// Added log
use Illuminate\Support\Facades\Log;

/**
 * Transaction Form Component
 * 
 * Provides a form to create and edit financial transactions.
 * Features:
 * - Income and expense transactions
 * - Transfer transactions
 * - Multi-currency support
 * - VAT and withholding calculations
 * - Installment transactions
 * - Subscription transactions
 * - Account management
 * - Category management
 * 
 * @package App\Livewire\Transaction
 */

class TransactionForm extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use InteractsWithActions;

    /** @var Transaction|null Edited transaction */
    public ?Transaction $transaction = null;

    /** @var array Form data */
    public $data = [];
    public bool $isCopyMode = false; // Property to track copy mode

    /** @var TransactionService Transaction service */
    private TransactionService $transactionService; // Set back to private

    /** @var CurrencyService Currency service */
    private CurrencyService $currencyService; // Set back to private

    /** @var SubscriptionTransactionServiceInterface Subscription service */
    private SubscriptionTransactionServiceInterface $subscriptionService; // Added and set private

    /**
     * Inject services when the component boots.
     *
     * @param TransactionService $transactionService Transaction service
     * @param CurrencyService $currencyService Currency service
     * @param SubscriptionTransactionServiceInterface $subscriptionService Subscription service
     * @return void
     */
    public function boot(
        TransactionService $transactionService,
        CurrencyService $currencyService,
        SubscriptionTransactionServiceInterface $subscriptionService // Eklendi
    ): void
    {
        $this->transactionService = $transactionService;
        $this->currencyService = $currencyService;
        $this->subscriptionService = $subscriptionService; // Eklendi
    }

     /**
     * When the component is mounted, it is executed
     *
     * @param Transaction|null $transaction Transaction to be edited
     * @return void
     */

    public function mount($transaction = null): void
    {
        $this->isCopyMode = false;

        // Check and set copy mode
        if (request()->filled('copy_from')) { 

            $this->isCopyMode = true; 
            $originalTransactionId = (int) request()->query('copy_from');
            $originalTransaction = Transaction::find($originalTransactionId);
            if ($originalTransaction) {
                $this->fillDefaultFormData();
                $this->copyTransactionData($originalTransaction);
                Notification::make()
                    ->title('تم نسخ بيانات المعاملة.')
                    ->info()
                    ->send();
            } else {
                 Notification::make()
                    ->title('لم يتم العثور على المعاملة المراد نسخها.')
                    ->danger()
                    ->send();
                 $this->fillDefaultFormData();
                 $this->isCopyMode = false; // If copy fails, close the mode
            }
        }
        // Check edit mode
        elseif ($transaction) {
            $this->transaction = $transaction;
            $this->fillDefaultFormData();
            $this->loadTransactionData();
        }
        // New transaction mode
        else {
            $this->fillDefaultFormData();
        }
    }

    // Helper method to fill default form data
    private function fillDefaultFormData(): void
    {
         $this->form->fill([
            'type' => null,
            'payment_method' => PaymentMethodEnum::CASH->value,
            'currency' => 'YER',
            'exchange_rate' => 1,
            'date' => now(),
            'is_subscription' => false,
            'is_taxable' => false,
            'user_id' => auth()->id(),
            'account_select' => null,
            // Other fields that need to be reset can be added here
            'category_id' => null,
            'customer_id' => null,
            'supplier_id' => null,
            'amount' => null,
            'try_equivalent' => null,
            'fee_amount' => null,
            'description' => null,
            'installments' => null,
            'remaining_installments' => null,
            'monthly_amount' => null,
            'next_payment_date' => null,
            'subscription_period' => null,
            'tax_rate' => null,
            'tax_amount' => null,
            'has_withholding' => false,
            'withholding_rate' => null,
            'withholding_amount' => null,
            'status' => 'completed',
            'reference_id' => null,
        ]);
    }

    /**
     * Loads transaction data into the form
     * 
     * @return void
     */
    private function loadTransactionData(): void
    {
        $formData = [
            'type' => $this->transaction->type,
            'payment_method' => $this->transaction->payment_method?->value ?? PaymentMethodEnum::CASH->value,
            'currency' => $this->transaction->currency,
            'exchange_rate' => $this->transaction->exchange_rate ?? 1,
            'date' => $this->transaction->date,
            'is_subscription' => $this->transaction->is_subscription,
            'is_taxable' => $this->transaction->is_taxable,
            'user_id' => $this->transaction->user_id,
            'category_id' => $this->transaction->category_id,
            'customer_id' => $this->transaction->customer_id,
            'supplier_id' => $this->transaction->supplier_id,
            'amount' => $this->transaction->amount,
            'try_equivalent' => $this->transaction->try_equivalent,
            'fee_amount' => $this->transaction->fee_amount,
            'description' => $this->transaction->description,
            'installments' => $this->transaction->installments,
            'remaining_installments' => $this->transaction->remaining_installments,
            'monthly_amount' => $this->transaction->monthly_amount,
            'next_payment_date' => $this->transaction->next_payment_date,
            'subscription_period' => $this->transaction->subscription_period,
            'auto_renew' => $this->transaction->auto_renew,
            'tax_rate' => $this->transaction->tax_rate,
            'tax_amount' => $this->transaction->tax_amount,
            'has_withholding' => $this->transaction->has_withholding,
            'withholding_rate' => $this->transaction->withholding_rate,
            'withholding_amount' => $this->transaction->withholding_amount,
            'status' => $this->transaction->status,
            'reference_id' => $this->transaction->reference_id,
            'account_select' => null,
        ];
        
        // For non-cash transactions, set account selection
        if ($this->transaction->payment_method && $this->transaction->payment_method->value !== PaymentMethodEnum::CASH->value) {
            if ($this->transaction->type === 'income') {
                $formData['account_select'] = $this->transaction->destination_account_id;
            } else {
                $formData['account_select'] = $this->transaction->source_account_id;
            }
        }

        // Tax calculations
        if ($this->transaction->is_taxable && $this->transaction->tax_rate) {
            $taxRate = $this->transaction->tax_rate / 100;
            $netAmount = $this->transaction->amount / (1 + $taxRate);
            $formData['tax_amount'] = round($this->transaction->amount - $netAmount, 2);
        }

        // Currency setup
        if ($this->transaction->currency === 'YER') {
            $formData['exchange_rate'] = 1;
        }

        // Installment information
        if ($this->transaction->installments > 1) {
            $formData['installments'] = $this->transaction->installments;
            $formData['remaining_installments'] = $this->transaction->remaining_installments;
            $formData['monthly_amount'] = round($this->transaction->amount / $this->transaction->installments, 2);
        }

        // Load form data
        $this->form->fill($formData);
    }

    /**
     * Copies data from the given transaction to the form (for quick transactions)
     *
     * @param Transaction $originalTransaction المعاملة المراد نسخها
     * @return void
     */
    private function copyTransactionData(Transaction $originalTransaction): void
    {
        // Get all relevant data from the original transaction
        $formData = [
            'type' => $originalTransaction->type,
            'category_id' => $originalTransaction->category_id,
            'customer_id' => $originalTransaction->customer_id,
            'supplier_id' => $originalTransaction->supplier_id,
            'amount' => $originalTransaction->amount,
            'currency' => $originalTransaction->currency,
            'exchange_rate' => $originalTransaction->exchange_rate ?? 1,
            'try_equivalent' => $originalTransaction->try_equivalent,
            'fee_amount' => $originalTransaction->fee_amount,
            'description' => $originalTransaction->description,
            'payment_method' => $originalTransaction->payment_method?->value ?? PaymentMethodEnum::CASH->value,
            'source_account_id' => $originalTransaction->source_account_id,
            'destination_account_id' => $originalTransaction->destination_account_id,
            'is_taxable' => $originalTransaction->is_taxable,
            'tax_rate' => $originalTransaction->tax_rate,
            'tax_amount' => $originalTransaction->tax_amount,
            'has_withholding' => $originalTransaction->has_withholding,
            'withholding_rate' => $originalTransaction->withholding_rate,
            'withholding_amount' => $originalTransaction->withholding_amount,
            // Get original subscription information (for display in the form)
            'is_subscription' => $originalTransaction->is_subscription,
            'subscription_period' => $originalTransaction->subscription_period,
            // 'auto_renew' => $originalTransaction->auto_renew, // Kullanılmıyor
            'user_id' => auth()->id(), // Set user to current user
        ];

        // For new transaction, reset/set some fields
        $formData['date'] = now(); // Set to today
        // $formData['is_subscription'] = false; // Show in form, set to false when saving
        $formData['next_payment_date'] = null; // Not valid for new transaction
        $formData['remaining_installments'] = null; // Not valid for new transaction
        $formData['status'] = 'completed'; // Set to default status
        $formData['reference_id'] = $originalTransaction->id; // Keep old ID as reference

        // For account selection, set account_select
        $formData['account_select'] = null;
        if ($originalTransaction->payment_method && $originalTransaction->payment_method->value !== PaymentMethodEnum::CASH->value) {
            if ($originalTransaction->type === 'income') {
                $formData['account_select'] = $originalTransaction->destination_account_id;
            } else {
                $formData['account_select'] = $originalTransaction->source_account_id;
            }
        }

        // Load form data
        $this->form->fill($formData);
    }

    /**
     * Determines the payment method
     * 
     * @param Transaction $transaction Transaction
     * @return string|null Payment method
     */
    private function getPaymentMethod(Transaction $transaction): ?string
    {
        // First check payment_method field
        if ($transaction->payment_method) {
            return $transaction->payment_method->value;
        }

        // If payment_method is not set, determine based on account type
        if ($transaction->sourceAccount) {
            return match($transaction->sourceAccount->type) {
                Account::TYPE_BANK_ACCOUNT => PaymentMethodEnum::BANK->value,
                Account::TYPE_CREDIT_CARD => PaymentMethodEnum::CREDIT_CARD->value,
                Account::TYPE_CRYPTO_WALLET => PaymentMethodEnum::CRYPTO->value,
                Account::TYPE_VIRTUAL_POS => PaymentMethodEnum::VIRTUAL_POS->value,
                default => PaymentMethodEnum::CASH->value
            };
        }

        // If destination_account is set (for income transactions)
        if ($transaction->destinationAccount) {
            return match($transaction->destinationAccount->type) {
                Account::TYPE_BANK_ACCOUNT => PaymentMethodEnum::BANK->value,
                Account::TYPE_CRYPTO_WALLET => PaymentMethodEnum::CRYPTO->value,
                Account::TYPE_VIRTUAL_POS => PaymentMethodEnum::VIRTUAL_POS->value,
                default => PaymentMethodEnum::CASH->value
            };
        }

        return PaymentMethodEnum::CASH->value;
    }

    /**
     * Returns the account ID
     * 
     * @param Transaction $transaction Transaction
     * @return int|null Account ID
     */
    private function getAccountId(Transaction $transaction): ?int
    {
        return $transaction->source_account_id ?? $transaction->destination_account_id;
    }

    /**
     * Calculates the exchange rate
     * 
     * @param string|null $currency Currency
     * @param Carbon|null $date Date
     * @return float|null Exchange rate
     */
    private function getExchangeRate(?string $currency, ?Carbon $date = null): ?float
    {
        if (!$currency || $currency === 'YER') {
            return 1.0;
        }

        try {
            // Use the injected service
            $rates = $this->currencyService->getExchangeRates($date);
            if (!$rates || !isset($rates[$currency])) {
                return null;
            }

            // Foreign Currency -> YER (use direct buying rate)
            return $rates[$currency]['buying'];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Form schema
     * 
     * @param Forms\Form $form Form object
     * @return Forms\Form Configured form
     */
    public function form(Forms\Form $form): Forms\Form
    {
        // Services are initialized in mount() method.

        $schema = [
            // بطاقة المعاملة الرئيسية
            Forms\Components\Section::make('معلومات المعاملة')
                ->schema([
                    ...$this->getTransactionDetailsSchema(),
                    ...$this->getCurrencyAndAmountSchema(),
                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2),
                    // Keep original ID of copied transaction
                   Forms\Components\Hidden::make('reference_id'), 
                ]),
        ];

        // Try to get subscription schema
        $subscriptionSchema = $this->getSubscriptionSchema();
        // Only add if not null
        if ($subscriptionSchema !== null) {
            $schema[] = $subscriptionSchema;
        }

        // Add taxation schema
        $schema[] = $this->getTaxationSchema();

        return $form
            ->schema($schema)
            ->statePath('data');
    }

    /**
     * Returns cached account options
     * 
     * @param string|null $type Account type
     * @return array Account options
     */
    private function getCachedAccountOptions(string $type = null): array
    {
        $cacheKey = $type ? "user_{auth()->id()}_accounts_{$type}" : "user_{auth()->id()}_accounts";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($type) {
            $query = Account::query()
                ->where('user_id', auth()->id())
                ->where('status', true)  // Only active accounts
                ->whereNull('deleted_at'); // Not deleted accounts
            
            if ($type) {
                $query->where('type', $type);
            }
            
            return $query->get()
                ->mapWithKeys(function ($account) {
                    $typeName = match($account->type) {
                        'bank_account' => 'بنك',
                        'credit_card' => 'بطاقة ائتمان',
                        'crypto_wallet' => 'عملة مشفرة',
                        'virtual_pos' => 'نقطة بيع افتراضية',
                        'cash' => 'نقدًا',
                        default => $account->type,
                    };
                    return [$account->id => "{$account->name} ({$account->currency}) - {$typeName}"];
                })->toArray();
        });
    }

    /**
     * Returns cached category options
     * 
     * @param string $type Category type
     * @return array Category options
     */
    private function getCachedCategoryOptions(string $type): array
    {
        $cacheKey = "user_{auth()->id()}_categories_{$type}";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($type) {
            return Category::query()
                ->where('user_id', auth()->id())
                ->where('type', $type)
                ->where('status', true)
                ->pluck('name', 'id')
                ->toArray();
        });
    }
    
    /**
     * Transaction details form schema
     * 
     * @return array Form components
     */
    protected function getTransactionDetailsSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->label('نوع المعاملة')
                    ->options([
                        'income' => 'ايراد',
                        'expense' => 'مصروف',
                    ])
                    ->placeholder('حدد نوع المعاملة')
                    ->native(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // When state changes, reset form fields
                        $set('category_id', null);
                        $set('payment_method', PaymentMethodEnum::CASH->value);
                        $set('account_select', null);
                        $set('source_account_id', null);
                        $set('destination_account_id', null);
                        $set('customer_id', null);
                        $set('currency', 'YER');
                        $set('exchange_rate', 1);
                        $set('amount', null);
                        $set('try_equivalent', null);
                        $set('fee_amount', null);
                        $set('description', null);
                        $set('installments', null);
                        $set('remaining_installments', null);
                        $set('monthly_amount', null);
                        // $set('next_payment_date', null); // Not reset for subscription
                        // $set('is_subscription', false); // Not reset for subscription
                        // $set('subscription_period', null); // Not reset for subscription
                        // $set('auto_renew', false); // Not used, can be reset or removed
                        $set('is_taxable', false);
                        $set('tax_rate', null);
                        $set('tax_amount', null);
                        $set('has_withholding', false);
                        $set('withholding_rate', null);
                        $set('withholding_amount', null);
                    }),

                Forms\Components\Select::make('category_id')
                    ->label('فئة')
                    ->options(function (callable $get): array {
                        $type = $get('type');
                        if (!$type) {
                            return []; // Return empty if no type is selected
                        }
                        // Fetch categories based on the selected type
                        return Category::query()
                            ->where('type', $type)
                            ->where('status', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder(fn (callable $get) => $get('type') ? 'يرجى تحديد الفئة' : 'أولاً حدد نوع المعاملة')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->live()
                    ->reactive(), // Make category field reactive

            ]),

            Forms\Components\Select::make('customer_id')
                ->label('العميل')
                ->options(function () {
                    return \App\Models\Customer::query()
                        ->where('status', true)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->placeholder('يرجى تحديد العميل')
                ->searchable()
                ->preload()
                ->native(false)
                ->visible(fn (callable $get) => $get('type') === 'income'),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options(function (callable $get) {
                        // Income options
                        if ($get('type') === 'income') {
                            return [
                                PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                                PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                                PaymentMethodEnum::CRYPTO->value => PaymentMethodEnum::CRYPTO->label(),
                                PaymentMethodEnum::VIRTUAL_POS->value => PaymentMethodEnum::VIRTUAL_POS->label(),
                            ];
                        }
                        
                        // Expense options
                        if ($get('type') === 'expense') {
                            return [
                                PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                                PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                                PaymentMethodEnum::CRYPTO->value => PaymentMethodEnum::CRYPTO->label(),
                                PaymentMethodEnum::CREDIT_CARD->value => PaymentMethodEnum::CREDIT_CARD->label(),
                            ];
                        }

                        // Default is only cash
                        return [
                            PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                        ];
                    })
                    ->placeholder('يرجى تحديد طريقة الدفع')
                    ->default(PaymentMethodEnum::CASH->value)
                    ->disabled(fn (callable $get) => !in_array($get('type'), ['income', 'expense']))
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // When payment method changes, clear account selection
                        $set('account_select', null);
                        $set('source_account_id', null);
                        $set('destination_account_id', null);
                        $set('amount', null);
                        $set('try_equivalent', null);
                        $set('fee_amount', null);
                        $set('description', null);
                        $set('installments', null);
                        $set('remaining_installments', null);
                        $set('monthly_amount', null);
                        $set('next_payment_date', null);

                        // When crypto wallet is selected, automatically select USD currency
                        if ($state === PaymentMethodEnum::CRYPTO->value) {
                            $set('currency', 'USD');
                            // USD kuru al
                            $exchangeRate = app(CurrencyService::class)->getExchangeRate('USD');
                            if ($exchangeRate) {
                                $set('exchange_rate', $exchangeRate['buying']);
                            }
                        } else {
                            // For non-crypto transactions, return to YER
                            $set('currency', 'YER');
                            $set('exchange_rate', 1);
                        }
                    }),

                Forms\Components\Select::make('account_select')
                    ->label(function (callable $get) {
                        return match($get('payment_method')) {
                            PaymentMethodEnum::BANK->value => 'حساب مصرفي',
                            PaymentMethodEnum::CREDIT_CARD->value => 'بطاقة ائتمان',
                            PaymentMethodEnum::CRYPTO->value => 'محفظة العملات المشفرة',
                            PaymentMethodEnum::VIRTUAL_POS->value => 'نقطة بيع افتراضية',
                            default => 'حساب'
                        };
                    })
                    ->options(function (callable $get) {
                        if ($get('payment_method') === PaymentMethodEnum::CASH->value) {
                            return [];
                        }

                        $query = Account::query()
                            ->where('user_id', auth()->id())
                            ->where('status', true)
                            ->whereNull('deleted_at');

                        // Filter account type based on payment method
                        $query->when($get('payment_method') === PaymentMethodEnum::BANK->value, 
                            fn($q) => $q->where('type', Account::TYPE_BANK_ACCOUNT)
                        )
                        ->when($get('payment_method') === PaymentMethodEnum::CREDIT_CARD->value, 
                            fn($q) => $q->where('type', Account::TYPE_CREDIT_CARD)
                        )
                        ->when($get('payment_method') === PaymentMethodEnum::CRYPTO->value, 
                            fn($q) => $q->where('type', Account::TYPE_CRYPTO_WALLET)
                        )
                        ->when($get('payment_method') === PaymentMethodEnum::VIRTUAL_POS->value, 
                            fn($q) => $q->where('type', Account::TYPE_VIRTUAL_POS)
                        );

                        return $query->get()
                            ->mapWithKeys(fn ($account) => [
                                $account->id => match($account->type) {
                                    Account::TYPE_BANK_ACCOUNT, Account::TYPE_CRYPTO_WALLET => "{$account->name} ({$account->formatted_balance}) - {$account->currency}",
                                    default => $account->name,
                                }
                            ])
                            ->toArray();
                    })
                    ->placeholder(function (callable $get) {
                        if ($get('payment_method') === PaymentMethodEnum::CASH->value) {
                            return 'أولاً، اختر طريقة الدفع الخاصة بك';
                        }
                        return 'يرجى تحديد الحساب';
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->disabled(fn (callable $get) => $get('payment_method') === PaymentMethodEnum::CASH->value)
                    ->required(fn (callable $get) => in_array($get('payment_method'), [
                        PaymentMethodEnum::BANK->value,
                        PaymentMethodEnum::CREDIT_CARD->value,
                        PaymentMethodEnum::CRYPTO->value,
                        PaymentMethodEnum::VIRTUAL_POS->value
                    ]))
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        if ($state) {
                            $account = Account::find($state);
                            if ($account && $account->currency !== $get('currency')) {
                                Notification::make()
                                    ->info()
                                    ->title('معاملة صرف العملات')
                                    ->body("ستتم هذه المعاملة من حساب {$account->currency} بالعملة {$get('currency')}. سعر الصرف: {$get('exchange_rate')}")
                                    ->send();
                            }
                        }
                    }),
            ]),
        ];
    }

    /**
     * Currency and amount form schema
     * 
     * @return array Form components
     */
    private function getCurrencyAndAmountSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('currency')
                    ->label('العملة')
                    ->options(function (callable $get) {
                        if ($get('payment_method') === PaymentMethodEnum::CRYPTO->value) {
                            return ['USD' => 'Amerikan Doları ($)'];
                        }
                        return [
                            'YER' => 'ريال يمني (ر.ي)',
                            'USD' => 'دولار أمريكي ($)',
                            'EUR' => 'يورو (€)',
                            'GBP' => 'جنيه إسترليني (£)',
                        ];
                    })
                    ->default('YER')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        // Currency calculation
                        if ($state && $state !== 'YER') {
                            $date = $get('date') ? Carbon::parse($get('date')) : now();
                            $exchangeRate = $this->currencyService->getExchangeRate($state, $date);
                            
                            if ($exchangeRate !== null) {
                                $set('exchange_rate', $exchangeRate['buying']);
                                if ($get('amount')) {
                                    $set('try_equivalent', round($get('amount') * $exchangeRate['buying'], 2));
                                }
                            } else {
                                if (!$get('exchange_rate')) {
                                    $set('exchange_rate', 1);
                                }
                                
                                Notification::make()
                                    ->warning()
                                    ->title('لم نتمكن من الحصول على سعر الصرف')
                                    ->body('لم نتمكن من الحصول على سعر الصرف للتاريخ المحدد. يرجى إدخال سعر الصرف بشكل يدوي.')
                                    ->send();
                            }
                        } else {
                            $set('exchange_rate', 1);
                            $set('try_equivalent', $get('amount'));
                        }

                        // If account is selected, check for currency mismatch
                        if ($get('account_select')) {
                            $account = Account::find($get('account_select'));
                            if ($account && $account->currency !== $state) {
                                Notification::make()
                                    ->info()
                                    ->title('معاملة صرف العملات')
                                    ->body("ستتم هذه المعاملة من حساب {$account->currency} بالعملة {$state}. سعر الصرف: {$get('exchange_rate')}")
                                    ->send();
                            }
                        }
                    }),

                Forms\Components\TextInput::make('exchange_rate')
                    ->label('اجمالي')
                    ->numeric()
                    ->required(fn (callable $get) => $get('currency') !== 'YER')
                    ->default(1)
                    ->disabled(fn (callable $get) => $get('currency') === 'YER')
                    ->dehydrated()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        if ($state) {
                            $amount = floatval($get('amount') ?? 0);
                            $currency = $get('currency');
                            
                            if ($currency === 'USD') {
                                $set('try_equivalent', round($amount * $state, 2));
                            } else {
                                $set('try_equivalent', round($amount * $state, 2));
                            }
                        }
                    })
                    ->placeholder('قيمة سعر الصرف')
                    ->helperText('سعر الصرف لتحويل ما يعادل الريال اليمني الى دولار'),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ (الإجمالي)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        $amount = floatval($state ?? 0);
                        $exchangeRate = floatval($get('exchange_rate') ?? 1);
                        $currency = $get('currency');
                        
                        if ($currency && $currency !== 'YER') {
                            $tryAmount = $amount * $exchangeRate;
                            $set('try_equivalent', round($tryAmount, 2));
                        } else {
                            $set('try_equivalent', $amount);
                        }
                        
                        if ($get('is_taxable') && $get('tax_rate')) {
                            $taxRate = floatval($get('tax_rate')) / 100;
                            $netAmount = $amount / (1 + $taxRate);
                            $set('tax_amount', round($amount - $netAmount, 2));
                        }
                        
                        if ($get('is_taxable') && $get('has_withholding') && $get('withholding_rate')) {
                            $withholdingRate = floatval($get('withholding_rate')) / 100;
                            $set('withholding_amount', round($amount * $withholdingRate, 2));
                        }
                    })
                    ->prefix(fn (callable $get) => match($get('currency')) {
                        'YER' => 'ر.ي',
                        'USD' => '$',
                        'EUR' => '€',
                        'GBP' => '£',
                        default => '$',
                    }),

                Forms\Components\DatePicker::make('date')
                    ->label('تاريخ')
                    ->required()
                    ->default(now())
                    ->displayFormat('d.m.Y')
                    ->maxDate(now())
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        $currency = $get('currency');
                        if ($currency && $currency !== 'TRY') {
                            $date = $state ? Carbon::parse($state) : now();
                            
                            if ($date->isAfter(now())) {
                                Notification::make()
                                    ->danger()
                                    ->title('تاريخ غير صحيح')
                                    ->body('لا يمكن إضافة معاملة بتاريخ مستقبلي!')
                                    ->send();
                                    
                                $set('date', now()->format('Y-m-d'));
                                return;
                            }
                            
                            $exchangeRate = $this->currencyService->getExchangeRate($currency, $date);
                            
                            if ($exchangeRate !== null) {
                                $set('exchange_rate', $exchangeRate['buying']);
                                
                                if ($get('amount')) {
                                    $set('try_equivalent', round($get('amount') * $exchangeRate['buying'], 2));
                                }
                            } else {
                                if (!$get('exchange_rate')) {
                                    $set('exchange_rate', 1);
                                }
                                
                                Notification::make()
                                    ->warning()
                                    ->title('لم نتمكن من الحصول على سعر الصرف')
                                    ->body('لم نتمكن من الحصول على سعر الصرف للتاريخ المحدد. يرجى إدخال سعر الصرف بشكل يدوي.')
                                    ->send();
                            }
                        }
                    }),
            ]),

            Forms\Components\Grid::make(1)->schema([
                Forms\Components\TextInput::make('try_equivalent')
                    ->label('المقابل بالدولار')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
            ])->visible(fn (callable $get) => $get('currency') !== 'YER'),

            // Credit card installment section
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('installments')
                        ->label('Taksit Sayısı')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(36)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                            if ($state) {
                                $set('remaining_installments', $state);
                                
                                $amount = (float) $get('amount');
                                if ($amount > 0) {
                                    $monthlyAmount = round($amount / $state, 2);
                                    $set('monthly_amount', $monthlyAmount);
                                }
                            } else {
                                $set('remaining_installments', null);
                                $set('monthly_amount', null);
                            }
                        }),

                    Forms\Components\TextInput::make('monthly_amount')
                        ->label('مبلغ القسط الشهري')
                        ->disabled()
                        ->prefix(fn (callable $get) => match($get('currency')) {
                            'YER' => 'ر.ي',
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            default => '$',
                        }),
                ])
                ->visible(fn (callable $get) => $get('payment_method') === 'credit_card'),
        ];
    }

    /**
     * Subscription information form schema
     * 
     * @return Forms\Components\Section Subscription components
     */
    private function getSubscriptionSchema(): ?Forms\Components\Section // Return type nullable made
    {
        // In copy mode, don't create this section
        if ($this->isCopyMode) {
            return null;
        }

        return Forms\Components\Section::make('معلومات الاشتراك')
            ->schema([
                Forms\Components\Toggle::make('is_subscription')
                    ->label('اشتراك؟')
                    ->helperText('إذا كانت هذه المعاملة دفعة اشتراك متكررة منتظمة، يرجى تحديدها.')
                    ->default(false)
                    ->live(),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('subscription_period')
                            ->label('خطه الاشتراك')
                            ->options([
                                'daily' => 'يومي',
                                'weekly' => 'أسبوعي',
                                'monthly' => 'شهري',
                                'quarterly' => 'ربع سنوي',
                                'biannually' => 'نصف سنوي',
                                'annually' => 'سنوي',
                            ])
                            ->required(fn (callable $get) => $get('is_subscription'))
                            ->native(false)
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\DatePicker::make('next_payment_date')
                            ->label('الدفعة التالية')
                            ->displayFormat('d.m.Y')
                            ->default(fn () => now()->addMonth())
                            ->required(fn (callable $get) => $get('is_subscription'))
                            ->native(false),
                    ])
                    ->visible(fn (callable $get) => $get('is_subscription'))
            ])
            ->collapsible()
            ->collapsed(fn () => $this->isCopyMode);
    }

    /**
     * Taxation form schema
     * 
     * @return Forms\Components\Section الضرائب bileşenleri
     */
    private function getTaxationSchema(): Forms\Components\Section
    {
        return Forms\Components\Section::make('الضرائب')
            ->schema([
                Forms\Components\Grid::make(1)->schema([
                    Forms\Components\Select::make('is_taxable')
                        ->label('الضرائب')
                        ->options([
                            1 => 'نعم',
                            0 => 'لا',
                        ])
                        ->default(0)
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                $set('tax_rate', null);
                                $set('tax_amount', null);
                                $set('has_withholding', 0);
                                $set('withholding_rate', null);
                                $set('withholding_amount', null);
                            } else {
                                // If taxation is present, withholding is default 0 (none)
                                $set('has_withholding', 0);
                            }
                        }),
                ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('tax_rate')
                            ->label('KDV Oranı')
                            ->options([
                                0 => '%0',
                                1 => '%1',
                                8 => '%8',
                                10 => '%10',
                                18 => '%18',
                                20 => '%20',
                            ])
                            ->required(fn (callable $get) => $get('is_taxable'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 0 || $state === null) {
                                    $set('tax_amount', null);
                                } else if ($get('amount')) {
                                    $amount = $get('amount');
                                    $taxRate = $state / 100;
                                    $netAmount = $amount / (1 + $taxRate);
                                    $set('tax_amount', round($amount - $netAmount, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('ضريبة القيمة المضافة')
                            ->disabled()
                            ->numeric()
                            ->prefix(fn (callable $get) => match($get('currency')) {
                                'YER' => 'ر.ي',
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                default => '$',
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable')),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('has_withholding')
                            ->label('Stopaj')
                            ->options([
                                1 => 'Var',
                                0 => 'Yok',
                            ])
                            ->default(0)
                            ->required(fn (callable $get) => $get('is_taxable'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('withholding_rate', null);
                                    $set('withholding_amount', null);
                                }
                            }),

                        Forms\Components\TextInput::make('withholding_rate')
                            ->label('Stopaj Oranı (%)')
                            ->numeric()
                            ->required(fn (callable $get) => $get('has_withholding'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 0 || $state === null || !$get('has_withholding')) {
                                    $set('withholding_amount', null);
                                } else if ($get('amount')) {
                                    $amount = $get('amount');
                                    $withholdingRate = floatval($state) / 100;
                                    $set('withholding_amount', round($amount * $withholdingRate, 2));
                                }
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable')),

                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\TextInput::make('withholding_amount')
                            ->label('مبلغ الإيراد المحصل')
                            ->disabled()
                            ->numeric()
                            ->prefix(fn (callable $get) => match($get('currency')) {
                                'YER' => 'ر.ي',
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                default => '$',
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable') && $get('has_withholding')),
            ])
            ->collapsible()
            ->collapsed(false);
    }

    /**
     * Saves the form data
     * 
     * @throws \Exception Errors that can occur during the record
     * @return void
     */
    public function save(): void
    {
        $data = $this->form->getState();
        
        try {
            // Cash transaction check
            if ($data['payment_method'] === PaymentMethodEnum::CASH->value) {
                // Find or create cash account
                $cashAccount = Account::firstOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'type' => Account::TYPE_CASH,
                        'currency' => $data['currency'],
                        'status' => true,
                    ],
                    [
                        'name' => 'نقدًا',
                        'balance' => 0,
                    ]
                );

                // Assign account based on transaction type
                if ($data['type'] === 'income') {
                    $data['destination_account_id'] = $cashAccount->id;
                    $data['source_account_id'] = null;
                } elseif ($data['type'] === 'expense') {
                    $data['source_account_id'] = $cashAccount->id;
                    $data['destination_account_id'] = null;
                }
            } else {
                // Set new account selection
                if (!empty($data['account_select'])) {
                    if ($data['type'] === 'income') {
                        $data['destination_account_id'] = $data['account_select'];
                        $data['source_account_id'] = null;
                    } elseif ($data['type'] === 'expense') {
                        $data['source_account_id'] = $data['account_select'];
                        $data['destination_account_id'] = null;
                    }
                }
            }

            // In edit mode
            if ($this->transaction) {
                $transactionData = TransactionData::fromArray($data);
                // Use the injected service
                $this->transactionService->update($this->transaction, $transactionData);
            } else {
                // Credit card installment check
                if ($data['payment_method'] === PaymentMethodEnum::CREDIT_CARD->value && !empty($data['installments']) && $data['installments'] > 1) {
                    if ($data['type'] !== 'expense') {
                        throw new \Exception('تقسيط العملية يمكن حفظها فقط في حساب.');
                    }

                    $account = Account::query()
                        ->where('id', $data['account_select'])
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if (!$account) {
                        throw new \Exception('يرجى تحديد بطاقة ائتمان.');
                    }
                    
                    if ($account->type !== Account::TYPE_CREDIT_CARD) {
                        throw new \Exception('الحساب المحدد ليس بطاقة ائتمان.');
                    }

                    $data['source_account_id'] = $data['account_select'];
                    $data['destination_account_id'] = null;

                    $transactionData = TransactionData::fromArray($data);
                    // Use the injected service
                    $this->transactionService->create($transactionData);
                } else {
                    // Normal transaction creation
                    // Normal transaction creation - Subscription information will be taken from the form
                    
                    $transactionData = TransactionData::fromArray($data);
                    $newTransaction = $this->transactionService->create($transactionData); // Get the created transaction

                    // If this is a copied transaction (reference_id exists), update the original subscription's date
                    if (!empty($data['reference_id'])) {
                        $originalSubscription = Transaction::find($data['reference_id']);
                        // Check if the original transaction is actually a subscription and has a period
                        if ($originalSubscription && $originalSubscription->is_subscription && $originalSubscription->subscription_period) {
                            try {
                                // Calculate the new date using the original subscription's current next_payment_date
                                $newNextPaymentDate = $this->subscriptionService->calculateNextPaymentDate($originalSubscription);
                                $originalSubscription->update(['next_payment_date' => $newNextPaymentDate]);
                                Log::info('الدفعة التالية للاشتراك تم تحديثها.', ['id' => $originalSubscription->id, 'new_date' => $newNextPaymentDate->toDateString()]);
                            } catch (\Exception $e) {
                                Log::error('خطأ أثناء تحديث الدفعة التالية للاشتراك.', [
                                    'original_subscription_id' => $originalSubscription->id,
                                    'error' => $e->getMessage()
                                ]);
                                // Optional: User can be notified but the operation should continue.
                                // Notification::make()->warning()->title('Orijinal abonelik تاريخi güncellenemedi.')->send();
                            }
                        }
                    }
                }
            }

            Notification::make()->success()->title('تم حفظ العملية بنجاح.')->send(); 
            $this->redirectRoute('admin.transactions.index', navigate: true);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('خطأ!')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Cancels the form operation
     * 
     * @return void
     */
    public function cancel(): void
    {
        $this->redirectRoute('admin.transactions.index', navigate: true);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.transaction.transaction-form');
    }
} 