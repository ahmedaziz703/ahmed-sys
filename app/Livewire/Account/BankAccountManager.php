<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Services\Account\Implementations\AccountService;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use App\DTOs\Account\AccountData;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use App\Enums\PaymentMethodEnum;

/**
 * Bank Account Manager Component
 * 
 * Livewire component to manage only bank accounts.
 * Provides detailed operations and features for bank accounts.
 * 
 * Features:
 * - Bank account creation/editing/deletion
 * - Inter-account transfers
 * - Bank account transfers
 * - Bank account operations (deposit/withdrawal)
 * - Currency conversions
 * - Advanced filtering and search
 * - View transaction history
 */
class BankAccountManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Account service */
    private AccountService $accountService;

    /** @var CurrencyService Currency service */
    private CurrencyService $currencyService;

    /**
     * Initialize the component
     * 
     * @param AccountService $accountService Account service
     * @param CurrencyService $currencyService Currency service
     * @return void
     */
    public function boot(AccountService $accountService, CurrencyService $currencyService): void 
    {
        $this->accountService = $accountService;
        $this->currencyService = $currencyService;
    }

    /**
     * Configure the bank account list table
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('type', Account::TYPE_BANK_ACCOUNT)
            )
            ->emptyStateHeading('حساب مصرفي لم يتم العثور عليه')
            ->emptyStateDescription('أنشاء حساب مصرفي')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الحساب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('details.bank_name')
                    ->label('البنك')
                    ->getStateUsing(fn (Account $record) => $record->details['bank_name'] ?? 'Bilinmiyor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('العملة')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('رصيد المستخدم')
                    ->money(fn (Account $record) => $record->currency)
                    ->color(fn (Account $record) => $record->balance < 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('YER مقابل')
                    ->getStateUsing(function (Account $record) {
                        // If the account is already YER, return the balance directly
                        if ($record->currency === 'YER') {
                            return $record->balance;
                        }
                        
                        // Get the current exchange rate
                        $exchangeRate = $this->currencyService->getExchangeRate($record->currency);
                        
                        // If the exchange rate is not found, return null
                        if (!$exchangeRate) {
                            return null;
                        }
                        
                        // Calculate the YER equivalent using the buying rate
                        return $record->balance * $exchangeRate['buying'];
                    })
                    ->money('YER')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('الحاله')
                    ->onColor('success')
                    ->offColor('danger')
                    ->extraAttributes(['class' => 'compact-toggle'])
                    ->afterStateUpdated(function (Account $record, $state) {
                        $statusText = $state ? 'تفعيل' : 'الغاء';
                        Notification::make()
                            ->title("{$record->name} تم {$statusText} تحديث الحاله ")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('العملة')
                    ->multiple()
                    ->native(false)
                    ->options([
                        'YER' => 'ريال يمني',
                        'USD' => 'Amerikan Doları',
                        'EUR' => 'Euro',
                        'GBP' => 'İngiliz Sterlini',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        if (!empty($data['values'])) {
                            $query->whereIn('currency', $data['values']);
                        }
                    }),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('الحاله')
                    ->native(false)
                    ->query(function (Builder $query, array $data): void {
                        if (isset($data['value'])) {
                            $query->where('status', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_history')
                    ->label('تحويلات الحساب')
                    ->icon('heroicon-o-clock')
                    ->url(fn (Account $record): string => route('admin.accounts.history', $record->id))
                    ->openUrlInNewTab(false)
                    ->extraAttributes(['wire:navigate' => true])
                    ->color('gray')
                    ->visible(fn () => auth()->user()->can('bank_accounts.history')),
                Tables\Actions\EditAction::make()
                    ->modalHeading('حساب مصرفي تعديل')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalCancelActionLabel('الغاء')
                    ->visible(fn () => auth()->user()->can('bank_accounts.edit'))
                    ->form($this->getFormSchema())
                    ->mutateRecordDataUsing(function (array $data) {
                        $account = Account::find($data['id']);
                        $details = $account->details ?? [];
                        $data['details'] = [
                            'bank_name' => $details['bank_name'] ?? null,
                            'account_number' => $details['account_number'] ?? null,
                            'iban' => $details['iban'] ?? null,
                            'branch_code' => $details['branch_code'] ?? null,
                            'branch_name' => $details['branch_name'] ?? null,
                        ];
                        return $data;
                    })
                    ->using(function (Account $record, array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_BANK_ACCOUNT,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'],
                            'status' => $data['status'],
                            'details' => [
                                'bank_name' => $data['details']['bank_name'],
                                'account_number' => $data['details']['account_number'] ?? null,
                                'iban' => $data['details']['iban'] ?? null,
                                'branch_code' => $data['details']['branch_code'] ?? null,
                                'branch_name' => $data['details']['branch_name'] ?? null,
                            ],
                        ]);
                        return $this->accountService->updateAccount($record, $accountData);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('حساب مصرفي حذف')
                    ->modalDescription('هل انت متاكد من حذف الحساب؟')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('تم الحذف الحساب المصرفي بنجاح')
                    ->visible(fn () => auth()->user()->can('bank_accounts.delete'))
                    ->label('حذف')
                    ->using(function (Account $record) {
                        return $this->accountService->delete($record);
                    }),
                Tables\Actions\Action::make('transfer')
                    ->label('تحويل')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->modalHeading('تحويلات دوليه')
                    ->modalDescription('هل انت متاكد من التحويل؟')
                    ->visible(fn (Account $record): bool => 
                        auth()->user()->can('bank_accounts.transfers') &&
                        Account::where('id', '!=', $record->id)
                            ->where('status', true)
                            ->where('type', Account::TYPE_BANK_ACCOUNT)
                            ->exists() && 
                        $record->balance > 0
                    )
                    ->form(function (Account $record) {
                        return [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('target_account_id')
                                        ->label('نوع الحساب')
                                        ->options(function () use ($record) {
                                            return Account::where('id', '!=', $record->id)
                                                ->where('status', true)
                                                ->where('type', Account::TYPE_BANK_ACCOUNT)
                                                ->get()
                                                ->mapWithKeys(function ($account) {
                                                    // Bakiyeyi doğrudan account'tan al
                                                    $balance = $account->balance;
                                                    
                                                    // Bakiyeyi formatla
                                                    $formattedBalance = number_format($balance, 2, ',', '.') . " {$account->currency}";
                                                    
                                                    return [
                                                        $account->id => "{$account->name} ({$formattedBalance})"
                                                    ];
                                                });
                                        })
                                        ->required()
                                        ->searchable()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            // Önce tüm değerleri temizle
                                            $set('source_amount', null);
                                            $set('target_amount', null);
                                            $set('exchange_rate', null);

                                            // Sonra جديد kur حسابla
                                            if (!$state) return;
                                            
                                            $targetAccount = Account::find($state);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = $get('transaction_date') 
                                                    ? Carbon::parse($get('transaction_date'))
                                                    : now();

                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('تعذّر الحصول على معلومات سعر الصرف');

                                                // Kur حسابla
                                                if ($targetAccount->currency === $record->currency) {
                                                    $crossRate = 1;
                                                } elseif ($record->currency === 'YER') {
                                                    $crossRate = 1 / $rates[$targetAccount->currency]['selling'];
                                                } elseif ($targetAccount->currency === 'YER') {
                                                    $crossRate = $rates[$record->currency]['buying'];
                                                } else {
                                                    $crossRate = $rates[$record->currency]['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('لم يتم الحصول على معلومات سعر الصرف')
                                                    ->warning()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(6),

                                    Forms\Components\TextInput::make('source_balance')
                                        ->label('الرصيد الحالي')
                                        ->default(number_format($record->balance, 2, ',', '.') . " {$record->currency}")
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(3),

                                    Forms\Components\DatePicker::make('transaction_date')
                                        ->label('تاريخ المعاملة')
                                        ->default(now())
                                        ->maxDate(now())
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            // Hedef حساب seçili değilse çık
                                            $targetAccountId = $get('target_account_id');
                                            if (!$targetAccountId) return;
                                            
                                            $targetAccount = Account::find($targetAccountId);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = Carbon::parse($state);
                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('تعذّر الحصول على معلومات سعر الصرف');

                                                // جديد تاريخe göre kur حسابla
                                                if ($targetAccount->currency === $record->currency) {
                                                    $crossRate = 1;
                                                } elseif ($record->currency === 'YER') {
                                                    $crossRate = 1 / $rates[$targetAccount->currency]['selling'];
                                                } elseif ($targetAccount->currency === 'YER') {
                                                    $crossRate = $rates[$record->currency]['buying'];
                                                } else {
                                                    $crossRate = $rates[$record->currency]['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));

                                                $sourceAmount = (float) $get('source_amount');
                                                if ($sourceAmount > 0) {
                                                    $targetAmount = number_format($sourceAmount * $crossRate, 4, '.', '');
                                                    $set('target_amount', $targetAmount);
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('تعذّر الحصول على معلومات سعر الصرف')
                                                    ->warning()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('source_amount')
                                        ->label(fn () => "المبلغ المراد ارساله ({$record->currency})")
                                        ->required()
                                        ->numeric(4)
                                        ->minValue(0.0001)
                                        ->prefix($record->currency)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (!$state) return;
                                            
                                            $exchangeRate = (float) $get('exchange_rate');
                                            if ($exchangeRate > 0) {
                                                // المبلغ المستلمı حسابla ve 4 basamağa yuvarla
                                                $targetAmount = number_format($state * $exchangeRate, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            }
                                        })
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('target_amount')
                                        ->label(function ($get) {
                                            if (!$get('target_account_id')) return "المبلغ المستلم";
                                            $targetAccount = Account::find($get('target_account_id'));
                                            return "المبلغ المستلم (" . ($targetAccount?->currency ?? '') . ")";
                                        })
                                        ->prefix(fn ($get) => Account::find($get('target_account_id'))?->currency ?? '')
                                        ->numeric(4)
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('exchange_rate')
                                        ->label('سعر التحويل')
                                        ->helperText(function ($get) use ($record) {
                                            if (!$get('target_account_id')) return null;
                                            $targetAccount = Account::find($get('target_account_id'));
                                            if (!$targetAccount || $targetAccount->currency === $record->currency) return null;
                                            return "1 {$record->currency} = ? {$targetAccount->currency}";
                                        })
                                        ->numeric(4)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (!$state) return;

                                            // المبلغ المراد ارساله varsa, جديد kur ile المبلغ المستلمı حسابla
                                            $sourceAmount = (float) $get('source_amount');
                                            if ($sourceAmount > 0) {
                                                $targetAmount = number_format($sourceAmount * (float) $state, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            }
                                        })
                                        ->columnSpan(6),
                                ])
                                ->columns(6),
                        ];
                    })
                    ->requiresConfirmation()
                    ->action(function (array $data, Account $record): void {
                        try {
                            $targetAccount = Account::findOrFail($data['target_account_id']);
                            
                            // Prepare the required data for the transfer
                            $sourceAmount = (float) $data['source_amount'];
                            $targetAmount = (float) $data['target_amount'];
                            // Exchange rate is only required for different currencies
                            $exchangeRate = $record->currency !== $targetAccount->currency 
                                ? (float) $data['exchange_rate']
                                : 1;
                            $transactionDate = $data['transaction_date'];
                            
                            // Check if the source account has enough balance
                            if ($sourceAmount > $record->balance) {
                                throw new \Exception("لا يوجد رصيد كافٍ في حساب المصدر. عمليات التحويل بين الحسابات");
                            }
                            
                            // Find or create the transfer category
                            $transferCategory = Category::firstOrCreate(
                                ['user_id' => auth()->id(), 'name' => 'Transfer', 'type' => 'transfer'],
                                ['description' => 'لا يمكن إتمام عملية التحويل، الرصيد في حساب المصدر غير كافٍ']
                            );
                            
                            // Create the transaction
                            DB::transaction(function () use ($record, $targetAccount, $sourceAmount, $targetAmount, $exchangeRate, $transactionDate, $transferCategory) {
                                // Calculate the TRY equivalents
                                $sourceTryRate = $record->currency === 'YER' 
                                    ? 1 
                                    : ($this->currencyService->getExchangeRate($record->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $targetTryRate = $targetAccount->currency === 'YER'
                                    ? 1
                                    : ($this->currencyService->getExchangeRate($targetAccount->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $sourceTryEquivalent = $sourceAmount * $sourceTryRate;
                                $targetTryEquivalent = $targetAmount * $targetTryRate;
                                
                                // Transfer description
                                $description = "حول: {$record->name} -> {$targetAccount->name}";
                                if ($record->currency !== $targetAccount->currency) {
                                    $exchangeRate = round($exchangeRate, 6);
                                    $description .= " (Kur: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
                                }
                                
                                // Source account withdrawal
                                $sourceTransaction = Transaction::create([
                                    'user_id' => auth()->id(),
                                    'account_id' => $record->id,
                                    'amount' => -$sourceAmount,
                                    'currency' => $record->currency,
                                    'description' => $description,
                                    'date' => $transactionDate,
                                    'type' => Transaction::TYPE_TRANSFER,
                                    'status' => 'completed',
                                    'destination_account_id' => $targetAccount->id,
                                    'exchange_rate' => $sourceTryRate, // TRY conversion rate
                                    'try_equivalent' => -$sourceTryEquivalent, // TRY equivalent (negative)
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Update the source account balance
                                $record->balance -= $sourceAmount;
                                $record->save();
                                
                                // Destination account deposit
                                $targetTransaction = Transaction::create([
                                    'user_id' => auth()->id(),
                                    'account_id' => $targetAccount->id,
                                    'amount' => $targetAmount,
                                    'currency' => $targetAccount->currency,
                                    'description' => $description,
                                    'date' => $transactionDate,
                                    'type' => Transaction::TYPE_TRANSFER,
                                    'status' => 'completed',
                                    'reference_id' => $sourceTransaction->id,
                                    'source_account_id' => $record->id,
                                    'exchange_rate' => $targetTryRate, // TRY conversion rate
                                    'try_equivalent' => $targetTryEquivalent, // TRY equivalent (positive)
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Update the source transaction
                                $sourceTransaction->reference_id = $targetTransaction->id;
                                $sourceTransaction->save();
                                
                                // Update the target account balance
                                $targetAccount->balance += $targetAmount;
                                $targetAccount->save();
                            });

                            Notification::make()
                                ->title('تم النقل بنجاح')
                                ->body(function () use ($record, $targetAccount, $sourceAmount, $targetAmount) {
                                    if ($record->currency === $targetAccount->currency) {
                                        return "{$sourceAmount} {$record->currency} transfer edildi.";
                                    } else {
                                        $exchangeRate = round($targetAmount / $sourceAmount, 6);
                                        return "{$sourceAmount} {$record->currency} gönderildi, {$targetAmount} {$targetAccount->currency} alındı. (Kur: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
                                    }
                                })
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('فشل النقل')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف العناصر المحددة'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('أنشاء حساب مصرفي')
                    ->modalHeading('حساب مصرفي')
                    ->modalSubmitActionLabel('أنشاء')
                    ->modalCancelActionLabel('الغاء')
                    ->visible(fn () => auth()->user()->can('bank_accounts.create'))
                    ->form($this->getFormSchema())
                    ->createAnother(false)
                    ->using(function (array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_BANK_ACCOUNT,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'],
                            'status' => $data['status'],
                            'details' => [
                                'bank_name' => $data['details']['bank_name'],
                                'account_number' => $data['details']['account_number'] ?? null,
                                'iban' => $data['details']['iban'] ?? null,
                                'branch_code' => $data['details']['branch_code'] ?? null,
                                'branch_name' => $data['details']['branch_name'] ?? null,
                            ],
                        ]);
                        return $this->accountService->createAccount($accountData);
                    }),

                Tables\Actions\Action::make('atm_operations')
                    ->label('أنشاء بطاقه الصراف الآلي')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading('أنشاء بطاقه الصراف الآلي')
                    ->modalDescription('يمكنك إيداع أو سحب الأموال.')
                    ->visible(function (): bool { 
                        return auth()->user()->can('bank_accounts.transactions'); 
                    })
                    ->form([
                        Forms\Components\Select::make('account_id')
                            ->label('حساب')
                            ->options(function () {
                                return Account::query()
                                    ->where('user_id', auth()->id())
                                    ->where('type', Account::TYPE_BANK_ACCOUNT)
                                    ->where('status', true)
                                    ->get()
                                    ->mapWithKeys(fn ($account) => [
                                        $account->id => "{$account->name} ({$account->currency} - Bakiye: {$account->formatted_balance})"
                                    ]);
                            })
                            ->required()
                            ->live()
                            ->native(false),

                        Forms\Components\Select::make('operation_type')
                            ->label(' كتابه العمليه')
                            ->options([
                                'deposit' => 'إيداع',
                                'withdraw' => 'سحب الأموال'
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),

                        Forms\Components\DatePicker::make('date')
                            ->label(' تاريخ العمليه')
                            ->default(now())
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                    ])
                    ->action(function (array $data): void {
                        try {
                            DB::beginTransaction();

                            $account = Account::findOrFail($data['account_id']);
                            $amount = (float) $data['amount'];
                            
                            // Set the amount based on the operation type
                            $transactionAmount = $data['operation_type'] === 'deposit' ? $amount : -$amount;
                            
                            // Check if the account has enough balance for withdrawal
                            if ($data['operation_type'] === 'withdraw' && $account->balance < $amount) {
                                throw new \Exception('Yetersiz bakiye.');
                            }

                            // Create the transaction
                            Transaction::create([
                                'user_id' => auth()->id(),
                                'type' => $data['operation_type'] === 'deposit' ? 'atm_deposit' : 'atm_withdraw',
                                'amount' => $amount,
                                'currency' => $account->currency,
                                'date' => $data['date'],
                                'description' => $data['description'] ?: sprintf(
                                    '%s - %s',
                                    $account->name,
                                    $account->details['bank_name'] ?? 'البنك'
                                ),
                                'payment_method' => PaymentMethodEnum::CASH->value,
                                'status' => 'completed',
                                'source_account_id' => $data['operation_type'] === 'withdraw' ? $account->id : null,
                                'destination_account_id' => $data['operation_type'] === 'deposit' ? $account->id : null,
                                'try_equivalent' => $account->currency === 'YER' 
                                    ? $amount
                                    : $amount * ($this->currencyService->getExchangeRate($account->currency)['buying'] ?? 1),
                            ]);

                            // Update the account balance
                            $account->balance += $transactionAmount;
                            $account->save();

                            DB::commit();

                            Notification::make()
                                ->title('تمت العمليه بنجاح')
                                ->body(abs($amount) . " {$account->currency} كمية " . 
                                    ($data['operation_type'] === 'deposit' ? 'إيداع' : 'سحب') . 
                                    ' عمليه تم تنفيذها.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('فشل العمليه')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    /**
     * Create the account form schema
     * 
     * @return array Form components array
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('اسم الحساب')
                        ->required()
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('details.bank_name')
                        ->label('اسم البنك')
                        ->required()
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('details.branch_name')
                        ->label('اسم الفرع')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('details.account_number')
                        ->label('رقم الحساب')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('details.iban')
                        ->label('رقم الحساب المصرفي الدولي')
                        ->maxLength(34)
                        ->columnSpan(3),

                    Forms\Components\Select::make('currency')
                        ->label('العملة')
                        ->options([
                            'YER' => 'ريال يمني',
                            'USD' => 'الدولار الأمريكي',
                            'EUR' => 'اليورو',
                            'GBP' => 'الجنيه الإسترليني',
                            'SAR' => 'الريال السعودي',
                            'AED' => 'الدرهم الإماراتي',
                            'QAR' => 'الريال القطري',
                            'KWD' => 'الدينار الكويتي',
                            'BHD' => 'الدينار البحريني',
                            'OMR' => 'الريال العماني',
                            'JPY' => 'الين الياباني',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('balance')
                        ->label('الرصيد')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(3),

                    Forms\Components\Toggle::make('status')
                        ->label('الحالة')
                        ->default(true)
                        ->columnSpan(6),
                ])
                ->columns(6),
        ];
    }

    /**
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.bank-account-manager');
    }
}