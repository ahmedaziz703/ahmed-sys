<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\Currency\CurrencyService;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Account Transaction History Component
 * 
 * Livewire component to display and manage the full transaction history of a specific account.
 * Lists and filters income, expense, transfer and other financial transactions.
 * 
 * Features:
 * - Transaction history table
 * - Date and transaction type filtering
 * - Currency conversions
 * - View transaction details
 */
class AccountHistory extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;


    /** @var Account The account whose history will be displayed */
    public Account $account;

    /** @var CurrencyService Service for currency conversions */
    private CurrencyService $currencyService;

    /**
     * Component boot.
     * 
     * @param CurrencyService $currencyService Currency service
     * @return void
     */
    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Component mount.
     * 
     * @param Account $account Account whose history will be displayed
     * @return void
     */
    public function mount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * Configure the transaction history table.
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where(function ($query) {
                        $query->where('source_account_id', $this->account->id)
                            ->orWhere('destination_account_id', $this->account->id);
                    })
                    ->orderBy('id', 'desc')
            )
            ->emptyStateHeading('لم يتم العثور على تحويلات الحساب')
            ->emptyStateDescription('لم يتم تسجيل اي عمليه لهذا الحساب لحد الان')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('تاريخ')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'income' => 'ايراد',
                        'expense' => 'حساب',
                        'transfer' => 'تحويل',
                        'loan_payment' => 'دفعة قرض',
                        'payment' => 'دفعة',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'transfer' => 'warning',
                        'loan_payment' => 'gray',
                        'payment' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(function (Transaction $record) {
                        $amount = abs($record->amount);
                        $prefix = '';
                        
                        // Outflows from account
                        if ($record->source_account_id === $this->account->id) {
                            // Outflow (expense, transfer, loan payment)
                            $prefix = '-';
                        } 
                        // Inflows to account
                        elseif ($record->destination_account_id === $this->account->id) {
                            // Inflow (income, transfer, payment)
                            $prefix = '+';
                        }
                        
                        return "{$prefix}{$amount} {$record->currency}";
                    }),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('YER مقابل')
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->currency === 'YER') {
                            $tryAmount = abs($record->amount);
                            $prefix = '';
                            
                            // حسابtan para çıkışı الحالهları
                            if ($record->source_account_id === $this->account->id) {
                                // حسابtan para çıkışı (harcama, transfer, kredi ödemesi)
                                $prefix = '-';
                            } 
                            // Hesaba para girişi الحالهları
                            elseif ($record->destination_account_id === $this->account->id) {
                                // Hesaba para girişi (دخل, transfer, ödeme)
                                $prefix = '+';
                            }
                            
                            return "{$prefix}" . number_format($tryAmount, 2) . " YER";
                        }

                        // Use the stored YER equivalent
                        $tryAmount = abs($record->try_equivalent);
                        $prefix = '';
                        
                        // Outflows from account
                        if ($record->source_account_id === $this->account->id) {
                            // Outflow (expense, transfer, loan payment)
                            $prefix = '-';
                        } 
                        // Inflows to account
                        elseif ($record->destination_account_id === $this->account->id) {
                            // Inflow (income, transfer, payment)
                            $prefix = '+';
                        }
                        
                        return "{$prefix}" . number_format($tryAmount, 2) . " YER";
                    })
                    ->visible(fn () => $this->account->currency !== 'YER'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'income' => 'ايراد',
                        'expense' => 'حساب',
                        'transfer' => 'تحويل',
                        'loan_payment' => 'دفعة قرض',
                        'payment' => 'دفعة',
                    ])
                    ->native(false),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->native(false),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['start_date']) {
                            $query->where('date', '>=', $data['start_date']);
                        }
                        if ($data['end_date']) {
                            $query->where('date', '<=', $data['end_date']);
                        }
                    }),
            ]);
    }

    /**
     * Render the component view.
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.account-history');
    }
}