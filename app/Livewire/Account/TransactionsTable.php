<?php

namespace App\Livewire\Account;

use App\Models\Transaction;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

/**
 * Transactions Table Component
 * 
 * Livewire component to display the transaction history of a specific account.
 * Lists and filters the transaction details in table format.
 * 
 * Features:
 * - Transactions table
 * - Date filtering
 * - Transaction details view
 * - Installment information display
 * - TRY equivalent calculation
 */
class TransactionsTable extends Component implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    /** @var int|null The account ID to display the transaction history */
    public ?int $accountId = null; // Credit card ID

    /**
     * Configure the transactions table
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('user_id', auth()->id())
                    ->where('source_account_id', $this->accountId)
            )
            ->emptyStateHeading('عملية لم يتم العثور عليه')
            ->emptyStateDescription('لم يتم العثور عللى اي معاملات')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('تاريخ')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money(fn (Transaction $record) => $record->currency)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('YER مقابل')
                    ->getStateUsing(fn (Transaction $record) => $record->currency !== 'YER' && $record->exchange_rate ? $record->amount * $record->exchange_rate : $record->amount)
                    ->money('YER')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('installments')
                    ->label('القسط')
                    ->formatStateUsing(fn ($state) => $state > 1 ? "{$state} قسط" : 'دفعة واحدة')
                    ->toggleable(),
            ])
            ->filters([
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
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.transactions-table');
    }
}