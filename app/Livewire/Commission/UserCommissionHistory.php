<?php

namespace App\Livewire\Commission;

use App\Models\User;
use App\Models\Commission;
use App\Models\CommissionPayout;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

/**
 * User Commission History Component
 * 
 * This component displays and manages the commission history of users.
 * Features:
 * - Commission list view
 * - Commission statistics
 * - Commission payment creation
 * - Date-based filtering
 */
class UserCommissionHistory extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public User $user;

    // Table selection property
    public string $activeTable = 'commissions';

    /**
     * When the component is mounted, it runs
     */
    public function mount(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Commissions table
     */
    public function commissionsTable(Table $table): Table
    {
        return $table
            ->query(Commission::query()->where('user_id', $this->user->id))
            ->emptyStateHeading('لم يتم العثور على سجل عمولة')
            ->emptyStateDescription('لا يوجد سجل عموله حتى الان')
            ->emptyStateIcon('heroicon-o-document-text')
            ->columns([
                TextColumn::make('transaction.amount')
                    ->label('مبلغ المعاملة')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('commission_rate')
                    ->label('نسبة العمولة')
                    ->formatStateUsing(fn ($state) => '%' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('commission_amount')
                    ->label('مبلغ العمولة')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('تاريخ البداية')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                        DatePicker::make('until')
                            ->label('تاريخ النهاية')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when(
                            $data['from'],
                            fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                        )->when(
                            $data['until'],
                            fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                        );
                    })
            ])
            ->actions([
                Action::make('view_transaction')
                    ->label('عمليةi Görüntüle')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Commission $record): string => route('admin.transactions.edit', $record->transaction))
                    ->openUrlInNewTab()
            ])
            ->headerActions([
                $this->getCreatePayoutAction()
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable();
    }

    /**
     * Returns the create payout action
     */
    protected function getCreatePayoutAction(): CreateAction
    {
        return CreateAction::make('create_payout')
            ->label('إنشاء دفعة')
            ->modalHeading('إنشاء دفعة')
            ->modalSubmitActionLabel('إنشاء')
            ->modalCancelActionLabel('إلغاء')
            ->disableCreateAnother()
            ->form([
                TextInput::make('amount')
                    ->label('مبلغ الدفعة')
                    ->required()
                    ->prefix('$')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0,00'),
                DatePicker::make('payment_date')
                    ->label('تاريخ الدفع')
                    ->required()
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->default(now()),
            ])
            ->action(function (array $data): void {
                CommissionPayout::create([
                    'user_id' => $this->user->id,
                    'amount' => (float) str_replace(',', '.', $data['amount']),
                    'payment_date' => $data['payment_date'],
                ]);

                // Show notification
                Notification::make()
                    ->title('تم إنشاء الدفعة')
                    ->success()
                    ->send();

                $this->dispatch('commission-stats-updated');
            })
            ->visible(auth()->user()->can('users.commission.payment'));
    }

    /**
     * Payouts table
     */
    public function payoutsTable(Table $table): Table
    {
        return $table
            ->query(CommissionPayout::query()->where('user_id', $this->user->id))
            ->emptyStateHeading('لم يتم العثور على سجل دفعة')
            ->emptyStateDescription('لا يوجد سجل دفعه حتى الان')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->headerActions([
                $this->getCreatePayoutAction()
            ])
            ->columns([
                TextColumn::make('amount')
                    ->label('مبلغ الدفعة')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->label('تاريخ الدفع')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل الدفعة')
                    ->modalSubmitActionLabel('تحديث')
                    ->modalCancelActionLabel('إلغاء')
                    ->successNotificationTitle('تم تحديث الدفعة')
                    ->form([
                        TextInput::make('amount')
                            ->label('مبلغ الدفعة')
                            ->required()
                            ->prefix('$')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('0,00'),
                        DatePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert comma-separated numbers to dot-separated numbers
                        $data['amount'] = (float) str_replace(',', '.', $data['amount']);
                        return $data;
                    })
                    ->using(function (CommissionPayout $record, array $data): CommissionPayout {
                        $record->update([
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                        ]);
                        
                        // Update statistics
                        $this->dispatch('commission-stats-updated');
                        
                        return $record;
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->modalHeading('حذف الدفعة')
                    ->modalDescription('هل انت متاكد من حذف العموله؟')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->successNotificationTitle('تم حذف الدفعة')
                    ->after(function () {
                        $this->dispatch('commission-stats-updated');
                    })
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('تاريخ البداية')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                        DatePicker::make('until')
                            ->label('تاريخ النهاية')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when(
                            $data['from'],
                            fn ($query, $date) => $query->whereDate('payment_date', '>=', $date)
                        )->when(
                            $data['until'],
                            fn ($query, $date) => $query->whereDate('payment_date', '<=', $date)
                        );
                    })
            ])
            ->defaultSort('payment_date', 'desc')
            ->searchable();
    }

    /**
     * Main table configuration
     */
    public function table(Table $table): Table
    {
        if ($this->activeTable === 'commissions') {
            return $this->commissionsTable($table);
        }
        
        return $this->payoutsTable($table);
    }

    /**
     * Table change method
     */
    public function switchTable(string $table): void
    {
        $this->activeTable = $table;
        $this->resetTable();
    }

    /**
     * Renders the component view
     */
    public function render(): View
    {
        return view('livewire.commission.user-commission-history');
    }
} 