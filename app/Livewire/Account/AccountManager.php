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

/**
 * Account Manager Component
 * 
 * Livewire component to manage all account types (bank, credit card, crypto, virtual POS, cash).
 * Provides general management for all account types.
 * 
 * Features:
 * - All accounts list
 * - Account type and currency filtering
 * - Account status management
 * - Account details view
 * - Bulk account deletion
 */
class AccountManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Account service */
    private AccountService $accountService;

    /**
     * Initialize the component
     * 
     * @param AccountService $accountService Account service
     * @return void
     */
    public function boot(AccountService $accountService): void 
    {
        $this->accountService = $accountService;
    }

    /**
     * Configure the account list table
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('user_id', auth()->id())
                    ->with(['bankAccount', 'creditCard', 'cryptoWallet', 'virtualPos'])
            )
            ->emptyStateHeading('حساب لم يتم العثور عليه')
            ->emptyStateDescription('أنشاء حساب جديد.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('حساب Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('حساب Türü')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Account::TYPE_BANK_ACCOUNT => 'حساب مصرفي',
                        Account::TYPE_CREDIT_CARD => 'بطاقة ائتمان',
                        Account::TYPE_CRYPTO_WALLET => 'محفظة العملات المشفرةı',
                        Account::TYPE_VIRTUAL_POS => 'نقاط البيع الافتراضية',
                        Account::TYPE_CASH => 'نقدًا',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Account::TYPE_BANK_ACCOUNT => 'success',
                        Account::TYPE_CREDIT_CARD => 'danger',
                        Account::TYPE_CRYPTO_WALLET => 'warning',
                        Account::TYPE_VIRTUAL_POS => 'info',
                        Account::TYPE_CASH => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('currency')
                    ->label('العملة')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('رصيد المستخدم')
                    ->money(fn (Account $record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('YER مقابل')
                    ->money('YER')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('الحاله')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع الحساب')
                    ->options([
                        Account::TYPE_BANK_ACCOUNT => 'حساب مصرفي',
                        Account::TYPE_CREDIT_CARD => 'بطاقة ائتمان',
                        Account::TYPE_CRYPTO_WALLET => 'محفظة العملات المشفرةı',
                        Account::TYPE_VIRTUAL_POS => 'نقاط البيع الافتراضية',
                        Account::TYPE_CASH => 'نقدًا',
                    ])
                    ->native(false),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('العملة')
                    ->options([
                        'YER' => 'ريال يمني',
                        'USD' => 'Amerikan Doları',
                        'EUR' => 'Euro',
                        'GBP' => 'İngiliz Sterlini',
                    ])
                    ->native(false),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('الحاله')
                    ->placeholder('مكتما')
                    ->trueLabel('مفعل')
                    ->falseLabel('غير مفعل'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('تحديث')
                    ->icon('heroicon-s-eye')
                    ->url(fn (Account $record): string => match ($record->type) {
                        Account::TYPE_BANK_ACCOUNT => route('admin.accounts.bank'),
                        Account::TYPE_CREDIT_CARD => route('admin.accounts.credit-cards'),
                        Account::TYPE_CRYPTO_WALLET => route('admin.accounts.crypto'),
                        Account::TYPE_VIRTUAL_POS => route('admin.accounts.virtual-pos'),
                        default => route('admin.accounts.index'),
                    })
                    ->extraAttributes(['wire:navigate' => true])
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('تم التحيثت بنجاح'),
                ]),
            ])
            ->headerActions([
            ]);
    }

    /**
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.account-manager');
    }
} 