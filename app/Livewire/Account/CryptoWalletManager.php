<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Models\CryptoWallet;
use App\Services\Account\Implementations\AccountService;
use App\Services\Currency\CurrencyService;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\DTOs\Account\AccountData;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Transaction;
use Closure;

/**
 * Crypto Wallet Management Component
 * 
 * Customized Livewire component for managing cryptocurrency wallets.
 * Provides detailed operations and features for crypto wallets.
 * 
 * Features:
 * - Create/Edit/Delete crypto wallets
 * - Manage platform and wallet address
 * - Currency conversions (USD/TRY)
 * - Advanced filtering and search
 * - View transaction history
 */
class CryptoWalletManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Service for account operations */
    private AccountService $accountService;

    /** @var CurrencyService Service for currency conversions */
    private CurrencyService $currencyService;

    /**
     * Component boot.
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
     * Configure the crypto wallet list table.
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('type', Account::TYPE_CRYPTO_WALLET)
            )
            ->emptyStateHeading('لم يتم العثور على محفظة عملات مشفرة')
            ->emptyStateDescription('إنشاء محفظة عملات مشفرة')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المحفظة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('details.platform')
                    ->label('المنصة'),
                Tables\Columns\TextColumn::make('currency')
                    ->label('العملة')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('الرصيد الحالي داخل المحفظة')
                    ->formatStateUsing(function (Account $record) {
                        // Format balance with 2 decimals
                        return number_format($record->balance, 2) . ' ' . $record->currency;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('المقابل باليمني')
                    ->getStateUsing(function (Account $record) {
                        // If account is already YER, return balance directly
                        if ($record->currency === 'YER') {
                            return (float) $record->balance;
                        }
                        
                        try {
                            // Calculate YER equivalent for USD
                            $exchangeRateData = $this->currencyService->getExchangeRate('USD');
                            
                            if (!$exchangeRateData) {
                                return 0;
                            }
                            
                            $balance = (float) $record->balance;
                            $exchangeRate = (float) $exchangeRateData['buying'];
                            
                            // Calculate and return YER equivalent
                            return $balance * $exchangeRate;
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->money('YER')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('الحالة')
                    ->onColor('success')
                    ->offColor('danger')
                    ->extraAttributes(['class' => 'compact-toggle'])
                    ->afterStateUpdated(function (Account $record, $state) {
                        $statusText = $state ? 'نشط' : 'غير نشط';
                        Notification::make()
                            ->title("تم تغيير حالة محفظة {$record->name} إلى {$statusText}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('المنصة')
                    ->options([
                        'Binance' => 'باينانس',
                        'Bybit' => 'بت أواسيس',
                        'Kraken' => 'رين',
                        'Kucoin' => 'كراكن',
                        'Gateio' => 'غيت.آي.أو',
                        'Coinbase' => 'كوين بيس',
                        'MetaMask' => 'ميتا ماسك',
                        'Trust Wallet' => 'ترست ووليت',
                        'Other' => 'أخرى',
                    ])
                    ->multiple()
                    ->native(false)
                    ->query(function (Builder $query, array $data): void {
                        if (!empty($data['values'])) {
                            $query->whereIn('details->platform', $data['values']);
                        }
                    }),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('العملة')
                    ->options([
                        'YER' => 'YER (ريال يمني)',
                    ])
                    ->native(false),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('الحالة')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('تعديل محفظة العملات المشفرة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => auth()->user()->can('crypto_wallets.edit'))
                    ->form($this->getFormSchema()),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('حذف محفظة العملات المشفرة')
                    ->modalDescription('هل أنت متأكد من أنك تريد حذف هذه المحفظة؟')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->successNotificationTitle('تم حذف محفظة العملات المشفرة')
                    ->visible(fn () => auth()->user()->can('crypto_wallets.delete'))
                    ->label('حذف')
                    ->using(function (Account $record) {
                        return $this->accountService->delete($record);
                    }),
                Tables\Actions\Action::make('transfer')
                    ->label('تحويل')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->modalHeading('تحويل من محفظة العملات المشفرة إلى حساب مصرفي')
                    ->modalDescription('هل أنت متأكد من أنك تريد القيام بهذا التحويل؟')
                    ->visible(function (Account $record): bool { 
                        return auth()->user()->can('crypto_wallets.transfer') &&
                               Account::where('status', true)
                                   ->where('type', Account::TYPE_BANK_ACCOUNT)
                                   ->exists() && 
                               $record->balance > 0 &&
                               $record->status;
                    })
                    ->form(function (Account $record) {
                        return [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('target_account_id')
                                        ->label('الحساب المصرفي المستهدف')
                                        ->options(function () use ($record) {
                                            return Account::where('status', true)
                                                ->where('type', Account::TYPE_BANK_ACCOUNT)
                                                ->get()
                                                ->mapWithKeys(function ($account) {
                                                    // Read balance directly from account
                                                    $balance = $account->balance;
                                                    
                                                    // Format the balance
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
                                            // Reset all values first
                                            $set('source_amount', null);
                                            $set('target_amount', null);
                                            $set('exchange_rate', null);

                                            // Then calculate the new rate
                                            if (!$state) return;
                                            
                                            $targetAccount = Account::find($state);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = $get('transaction_date') 
                                                    ? Carbon::parse($get('transaction_date'))
                                                    : now();

                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('تعذر الحصول على سعر الصرف');

                                                // Calculate the cross rate
                                                if ($targetAccount->currency === $record->currency) {
                                                    // Same currency (USD -> USD) should be rate 1
                                                    $crossRate = 1;
                                                } elseif ($targetAccount->currency === 'YER') {
                                                    // USD -> YER uses USD buying rate
                                                    $crossRate = $rates['USD']['buying'];
                                                } else {
                                                    // USD -> Other: USD buying / target selling
                                                    $crossRate = $rates['USD']['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                // Ensure USD -> USD conversion is always 1
                                                if ($record->currency === 'USD' && $targetAccount->currency === 'USD') {
                                                    $crossRate = 1;
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('تعذر الحصول على سعر الصرف')
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
                                            // Exit if no target account selected
                                            $targetAccountId = $get('target_account_id');
                                            if (!$targetAccountId) return;
                                            
                                            $targetAccount = Account::find($targetAccountId);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = Carbon::parse($state);
                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('تعذر الحصول على سعر الصرف');

                                                // Recalculate cross rate for the new date
                                                if ($targetAccount->currency === $record->currency) {
                                                    // Same currency (USD -> USD) should be rate 1
                                                    $crossRate = 1;
                                                } elseif ($targetAccount->currency === 'YER') {
                                                    // USD -> YER uses USD buying rate
                                                    $crossRate = $rates['USD']['buying'];
                                                } else {
                                                    // USD -> Other: USD buying / target selling
                                                    $crossRate = $rates['USD']['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                // Ensure USD -> USD conversion is always 1
                                                if ($record->currency === 'USD' && $targetAccount->currency === 'USD') {
                                                    $crossRate = 1;
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));

                                                $sourceAmount = (float) $get('source_amount');
                                                if ($sourceAmount > 0) {
                                                    $targetAmount = number_format($sourceAmount * $crossRate, 4, '.', '');
                                                    $set('target_amount', $targetAmount);
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('تعذر الحصول على سعر الصرف')
                                                    ->warning()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('source_amount')
                                        ->label(fn () => "المبلغ المراد إرساله ({$record->currency})")
                                        ->required()
                                        ->numeric(4)
                                        ->minValue(0.0001)
                                        ->prefix($record->currency)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            if (!$state) return;
                                            
                                            // Balance check
                                            if ($state > $record->balance) {
                                                Notification::make()
                                                    ->title('رصيد غير كافٍ')
                                                    ->body("المبلغ المراد إرساله ({$state} {$record->currency}) لا يمكن أن يتجاوز الرصيد الحالي ({$record->balance} {$record->currency}).")
                                                    ->warning()
                                                    ->send();
                                                $set('source_amount', null);
                                                return;
                                            }
                                            
                                            $exchangeRate = (float) $get('exchange_rate');
                                            if ($exchangeRate > 0) {
                                                // Calculate target amount (4 decimals)
                                                $targetAmount = number_format($state * $exchangeRate, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            }
                                        })
                                        ->rules([
                                            'required',
                                            'numeric',
                                            'min:0.0001',
                                            'lte:' . $record->balance,
                                        ])
                                        ->validationMessages([
                                            'lte' => 'المبلغ المراد إرساله (:input ' . $record->currency . ') لا يمكن أن يتجاوز الرصيد الحالي (' . $record->balance . ' ' . $record->currency . ').',
                                        ])
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

                                            // If there is a source amount, calculate target with new rate
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
                            $exchangeRate = (float) $data['exchange_rate'];
                            $transactionDate = $data['transaction_date'];
                            
                            // Balance check
                            if ($sourceAmount > $record->balance) {
                                throw new \Exception("رصيد غير كافٍ. الحد الأقصى القابل للتحويل: " . number_format($record->balance, 2) . " {$record->currency}");
                            }
                            
                            // Transfer category
                            $transferCategory = Category::firstOrCreate(
                                ['user_id' => auth()->id(), 'name' => 'تحويل', 'type' => 'transfer'],
                                ['description' => 'حساب']
                            );
                            
                            // Create the transaction
                            DB::transaction(function () use ($record, $targetAccount, $sourceAmount, $targetAmount, $exchangeRate, $transactionDate, $transferCategory) {
                                // Calculate the YER equivalents
                                $sourceTryRate = $record->currency === 'YER' 
                                    ? 1 
                                    : ($this->currencyService->getExchangeRate($record->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $targetTryRate = $targetAccount->currency === 'YER'
                                    ? 1
                                    : ($this->currencyService->getExchangeRate($targetAccount->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $sourceTryEquivalent = $sourceAmount * $sourceTryRate;
                                $targetTryEquivalent = $targetAmount * $targetTryRate;
                                
                                // Transfer description
                                $description = "تحويل: {$record->name} -> {$targetAccount->name}";
                                if ($record->currency !== $targetAccount->currency) {
                                    $exchangeRate = round($exchangeRate, 6);
                                    $description .= " (السعر: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
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
                                    'exchange_rate' => $sourceTryRate, // YER conversion rate
                                    'try_equivalent' => -$sourceTryEquivalent, // YER equivalent (negative)
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
                                    'exchange_rate' => $targetTryRate, // YER conversion rate
                                    'try_equivalent' => $targetTryEquivalent, // YER equivalent (positive)
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
                                ->title('تم التحويل بنجاح')
                                ->body(function () use ($record, $targetAccount, $sourceAmount, $targetAmount) {
                                    if ($record->currency === $targetAccount->currency) {
                                        return "تم تحويل {$sourceAmount} {$record->currency}.";
                                    } else {
                                        $exchangeRate = round($targetAmount / $sourceAmount, 6);
                                        return "تم إرسال {$sourceAmount} {$record->currency}، واستلام {$targetAmount} {$targetAccount->currency}. (السعر: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
                                    }
                                })
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('فشل التحويل')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (Account $record): bool { 
                        return auth()->user()->can('crypto_wallets.transfer') &&
                               Account::where('status', true)
                                   ->where('type', Account::TYPE_BANK_ACCOUNT)
                                   ->exists() && 
                               $record->balance > 0 &&
                               $record->status;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحافظ المحددة')
                        ->visible(fn () => auth()->user()->can('crypto_wallets.delete')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إنشاء محفظة عملات مشفرة')
                    ->modalHeading('محفظة عملات مشفرة جديدة')
                    ->modalSubmitActionLabel('إنشاء')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => auth()->user()->can('crypto_wallets.create'))
                    ->form($this->getFormSchema())
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data) {
                        return [
                            'user_id' => auth()->id(),
                            'name' => $data['name'],
                            'type' => Account::TYPE_CRYPTO_WALLET,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'] ?? 0,
                            'details' => [
                                'platform' => $data['details']['platform'],
                                'wallet_address' => $data['details']['wallet_address'],
                            ],
                            'status' => $data['status'] ?? true,
                        ];
                    })
                    ->using(function (array $data) {
                        $accountData = AccountData::fromArray($data);
                        return $this->accountService->createCryptoWallet($accountData);
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
            Forms\Components\TextInput::make('name')
                ->label('اسم المحفظة')
                ->required(),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('details.platform')
                        ->label('منصة المحفظة')
                        ->options([
                            'Binance' => 'باينانس',
                            'Bybit' => 'بت أواسيس',
                            'Kraken' => 'رين',
                            'Kucoin' => 'كراكن',
                            'Gateio' => 'غيت.آي.أو',
                            'Coinbase' => 'كوين بيس',
                            'MetaMask' => 'ميتا ماسك',
                            'Trust Wallet' => 'ترست ووليت',
                            'Other' => 'أخرى',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('details.wallet_address')
                        ->label('عنوان المحفظة الرقمية')
                        ->required(),
                ])
                ->columns(2),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->label('العملة')
                        ->options([
                            'USD' => 'YER (ريال يمني)',
                        ])
                        ->default('USD')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('balance')
                        ->label('الرصيد الحالي داخل المحفظة')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
            Forms\Components\Toggle::make('status')
                ->label('حالة المحفظة')
                ->default(true),
        ];
    }

    /**
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.crypto-wallet-manager');
    }
}