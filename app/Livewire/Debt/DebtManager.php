<?php

declare(strict_types=1);

namespace App\Livewire\Debt;

use App\Models\{Debt, Customer, Supplier, Account};
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\DTOs\Debt\DebtData;
use App\Enums\PaymentMethodEnum;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use App\Livewire\Debt\Widgets\DebtStatsWidget;

/**
 * Debt/Receivable Manager Component
 * 
 * This component provides functionality to manage debt and receivables.
 * Features:
 * - Debt/receivable list view
 * - New debt/receivable creation
 * - Debt/receivable editing
 * - Debt/receivable deletion
 * - Status tracking
 * - Type and status filtering
 * - Statistics widgets
 * 
 * @package App\Livewire\Debt
 */
final class DebtManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var DebtServiceInterface Debt/receivable service */
    private DebtServiceInterface $service;

    /**
     * When the component is booted, the debt/receivable service is injected
     * 
     * @param DebtServiceInterface $service Debt/receivable service
     * @return void
     */
    public function boot(DebtServiceInterface $service): void 
    {
        $this->service = $service;
    }

    /**
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Debt::query()
            )
            ->emptyStateHeading('لم يتم العثور على ديون أو مستحقات')
            ->emptyStateDescription('أنشئ سجلًا جديدًا.')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع الدين')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'loan_payment' => 'مستحق الدفع (مدين)',
                        'debt_payment' => 'مستحق القبض (دائن)',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'loan_payment' => 'danger',
                        'debt_payment' => 'success',
                    }),
                Tables\Columns\TextColumn::make('related_entity')
                    ->label('الطرف المعني')
                    ->getStateUsing(function (?Debt $record): string {
                        if (!$record) return '-';
                        return $record->type === 'debt_payment' 
                            ? ($record->customer?->name ?? '-') 
                            : ($record->supplier?->name ?? '-');
                    })
                    ->searchable(['customer.name', 'supplier.name']),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(function (?Debt $record): string {
                        if (!$record) return '-';
                        
                        $amount = number_format((float)$record->amount, 2, ',', '.');
                        $currency = $record->currency;
                        
                        $currencyLabel = match($currency) {
                            'XAU' => 'ذهب',
                            'XAG' => 'فضة',
                            default => $currency
                        };
                        
                        return "{$amount} {$currencyLabel}";
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_price')
                    ->label('سعر الشراء')
                    ->money('YER')
                    ->visible(function (?Debt $record): bool {
                        return $record && $record->currency !== 'YER';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('sell_price')
                    ->label('سعر البيع')
                    ->money('YER')
                    ->visible(function (?Debt $record): bool {
                        return $record && $record->currency !== 'YER';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit_loss')
                    ->label('الربح/الخسارة')
                    ->money('YER')
                    ->visible(function (?Debt $record): bool {
                        return $record && $record->currency !== 'YER';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'completed' => 'تم الدفع',
                        'overdue' => 'متأخر',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'overdue' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'loan_payment' => 'مستحق الدفع (مدين)',
                        'debt_payment' => 'مستحق القبض (دائن)',
                    ])
                    ->native(false)
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'completed' => 'تم الدفع',
                        'overdue' => 'متأخر',
                    ])
                    ->native(false)
                    ->preload(),
            ])
            ->filtersFormColumns(1)
            ->filtersFormWidth('md')
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->label('تصفية')
            )
            ->defaultSort('due_date', 'asc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('تعديل')
                    ->modalSubmitActionLabel('تحديث')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => auth()->user()->can('debts.edit'))
                    ->successNotificationTitle('تم تحديث السجل')
                    ->form($this->getDebtForm())
                    ->using(function (Debt $record, array $data): Debt {
                        $debt = $this->service->update($record, DebtData::fromArray($data));
                        $this->dispatch('debt-updated')->to(DebtStatsWidget::class);
                        return $debt;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('حذف')
                    ->modalDescription('هل أنت متأكد من أنك تريد حذف هذا السجل؟')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => auth()->user()->can('debts.delete'))
                    ->successNotificationTitle('تم حذف السجل')
                    ->using(function (Debt $record) {
                        $this->service->delete($record);
                        $this->dispatch('debt-deleted')->to(DebtStatsWidget::class);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('سجل جديد')
                    ->modalHeading('دين/مستحق جديد')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => auth()->user()->can('debts.create'))
                    ->createAnother(false)
                    ->successNotificationTitle('تمت إضافة السجل')
                    ->form($this->getDebtForm())
                    ->using(function (array $data): Debt {
                        $debt = $this->service->create(DebtData::fromArray($data));
                        $this->dispatch('debt-created')->to(DebtStatsWidget::class);
                        return $debt;
                    }),
            ]);
    }

    /**
     * Creates the debt/receivable form
     * 
     * @return array Form components
     */
    protected function getDebtForm(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('النوع')
                                ->options([
                                    'loan_payment' => 'مستحق الدفع (مدين)',      
                                    'debt_payment' => 'مستحق القبض (دائن)',
                                ])                                
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('customer_id', null) && $set('supplier_id', null))
                                ->columnSpan(6),
                            Forms\Components\Select::make('customer_id')
                                ->label('العميل')
                                ->options(Customer::pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'debt_payment')
                                ->columnSpan(6),
                            Forms\Components\Select::make('supplier_id')
                                ->label('المورّد')
                                ->options(Supplier::pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'loan_payment')
                                ->columnSpan(6),
                        ])
                        ->columns(12)
                        ->columnSpan(12),
    
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('الوصف')
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\DatePicker::make('due_date')
                                ->label('تاريخ الاستحقاق')
                                ->default(now())
                                ->native(false)
                                ->format('d.m.Y')
                                ->columnSpan(6),
                        ])
                        ->columns(12)
                        ->columnSpan(12),
    
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('الكمية')
                                ->numeric()
                                ->required()
                                ->columnSpan(6)
                                ->live(),
                            Forms\Components\Select::make('currency')
                                ->label('العملة')
                                ->options([
                                    'YER' => 'YER',
                                    'USD' => 'USD',
                                    'EUR' => 'EUR',
                                    'GBP' => 'GBP',
                                    'XAU' => 'ذهب',
                                    'XAG' => 'فضة',
                                ])
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('buy_price', null) && $set('sell_price', null))
                                ->columnSpan(6),
                        ])
                        ->columns(12)
                        ->columnSpan(12),
    
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('الحالة')
                                ->options([
                                    'pending' => 'قيد الانتظار',
                                    'completed' => 'تم الدفع',
                                    'overdue' => 'متأخر',
                                ])
                                ->default('pending')
                                ->required()
                                ->native(false)
                                ->columnSpan(12),
                            Forms\Components\Textarea::make('notes')
                                ->label('ملاحظات')
                                ->rows(3)
                                ->columnSpan(12),
                        ])
                        ->columns(12)
                        ->columnSpan(12),
                ])
                ->columns(1),
        ];
    }
    
    /**
     * Returns the header widgets
     * 
     * @return array Widget classes
     */
    protected function getHeaderWidgets(): array
    {
        return [
            DebtStatsWidget::class,
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.debt.debt-manager');
    }
}