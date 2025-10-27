<?php

declare(strict_types=1);

namespace App\Livewire\Loan;

use App\Models\Loan;
use App\Models\Account;
use App\Enums\PaymentMethodEnum;
use App\Services\Loan\Contracts\LoanServiceInterface;
use App\DTOs\Loan\LoanData;
use App\Services\Payment\Implementations\PaymentService;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

/**
 * Loan Manager Component
 * 
 * This component provides functionality to manage loans.
 * Features:
 * - Loan list view
 * - New loan creation
 * - Loan editing
 * - Loan deletion
 * - Loan payment
 * - Loan status tracking
 * - Loan filtering (type, status)
 * 
 * @package App\Livewire\Loan
 */
class LoanManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    /** @var Loan|null Active loan */
    protected ?Loan $loan = null;

    /** @var LoanServiceInterface Loan service */
    private LoanServiceInterface $loanService;

    /** @var PaymentService Payment service */
    private PaymentService $paymentService;

    /**
     * When the component is booted, the services are injected
     * 
     * @param LoanServiceInterface $loanService Loan service
     * @param PaymentService $paymentService Payment service
     * @return void
     */
    public function boot(LoanServiceInterface $loanService, PaymentService $paymentService): void
    {
        $this->loanService = $loanService;
        $this->paymentService = $paymentService;
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
            ->query(Loan::query())
            ->emptyStateHeading('لم يتم العثور على قروض')
            ->emptyStateDescription('أنشئ قرضًا جديدًا.')
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('البنك')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('loan_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'personal' => 'فردي',
                        'business' => 'تجاري',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('قيمة القرض')
                    ->money('YER')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('monthly_payment')
                    ->label('الدفعة الشهرية')
                    ->money('YER')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money('YER')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد التسديد',
                        'paid' => 'تم السداد',
                        'overdue' => 'متأخر',
                        default => 'غير معروف', // ← إضافة هذا السطر يحل المشكلة
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'gray', // ← هذا السطر ضروري لتفادي الخطأ
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('next_payment_date')
                    ->label('الدفعة التالية')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['pending' => 'قيد الانتظار', 'paid' => 'تم السداد', 'overdue' => 'متأخر'])
                    ->native(false),
                Tables\Filters\SelectFilter::make('loan_type')
                    ->label('نوع القرض')
                    ->options(['personal' => 'فردي', 'business' => 'تجاري'])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('تعديل القرض')
                    ->visible(fn () => auth()->user()->can('loans.edit'))
                    ->form($this->getLoanForm())
                    ->action(function (array $data, Loan $loan): void {
                        $loanData = LoanData::fromArray([
                            'bank_name' => $data['bank_name'],
                            'loan_type' => $data['loan_type'],
                            'amount' => floatval($data['amount']),
                            'monthly_payment' => floatval($data['monthly_payment']),
                            'installments' => intval($data['installments']),
                            'remaining_installments' => intval($data['remaining_installments']),
                            'start_date' => $data['start_date'],
                            'next_payment_date' => $data['next_payment_date'],
                            'status' => $loan->status,
                            'notes' => $data['notes'],
                        ]);
                        $this->loanService->update($loan, $loanData);
                        Notification::make()
                            ->title('تم بنجاح!')
                            ->body('تم تحديث القرض بنجاح.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('payment')
                    ->label('إجراء دفعة')
                    ->color('success')
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn (Loan $record): bool => auth()->user()->can('loans.payments') && $record->status !== 'paid' && $record->remaining_installments > 0)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ الدفعة')
                            ->numeric()
                            ->required()
                            ->readonly()
                            ->default(fn (Loan $record) => $record->monthly_payment)
                            ->prefix('TL')
                            ->step(0.01)
                            ->minValue(0),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ آخر دفعة')
                            ->default(fn (Loan $record) => $record->next_payment_date)
                            ->required()
                            ->format('d.m.Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('تاريخ المعاملة')
                            ->default(now())
                            ->required()
                            ->format('d.m.Y')
                            ->native(false),
                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->options([
                                PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                                PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                                PaymentMethodEnum::CREDIT_CARD->value => PaymentMethodEnum::CREDIT_CARD->label(),
                            ])
                            ->native(false)
                            ->required()
                            ->default('cash')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('account_id', null);
                            }),
                        Forms\Components\Select::make('account_id')
                            ->label(function (callable $get) {
                                $method = $get('payment_method');
                                if ($method === PaymentMethodEnum::BANK->value) {
                                    return 'حساب مصرفي';
                                } elseif ($method === PaymentMethodEnum::CREDIT_CARD->value) {
                                    return 'بطاقة ائتمان';
                                }
                                return 'حساب';
                            })
                            ->options(function (callable $get) {
                                $method = $get('payment_method');
                                $type = null;
                                
                                if ($method === PaymentMethodEnum::BANK->value) {
                                    $type = Account::TYPE_BANK_ACCOUNT;
                                } elseif ($method === PaymentMethodEnum::CREDIT_CARD->value) {
                                    $type = Account::TYPE_CREDIT_CARD;
                                }
                                
                                if (!$type) {
                                    return [];
                                }
                                
                                return Account::where('user_id', auth()->id())
                                    ->where('type', $type)
                                    ->where('status', true)
                                    ->where('currency', 'YER')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->native(false)
                            ->visible(fn (callable $get) => in_array($get('payment_method'), [PaymentMethodEnum::BANK->value, PaymentMethodEnum::CREDIT_CARD->value])),
                    ])
                    ->action(function (Loan $record, array $data): void {
                        try {
                            $this->loanService->addPayment($record, $data);
                            
                            Notification::make()
                                ->title('تم بنجاح!')
                                ->body('تم حفظ الدفعة بنجاح.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('خطأ!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('حذف القرض')
                    ->modalDescription('هل أنت متأكد من أنك تريد حذف هذا القرض؟')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->successNotificationTitle('تم حذف القرض')
                    ->visible(fn () => auth()->user()->can('loans.delete'))
                    ->action(function (Loan $loan): void {
                        $result = $this->loanService->delete($loan);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('تم بنجاح!')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('تحذير')
                                ->body($result['message'])
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('قرض جديد')
                    ->modalHeading('قرض جديد')
                    ->visible(fn () => auth()->user()->can('loans.create'))
                    ->form($this->getLoanForm())
                    ->createAnother(false)
                    ->action(function (array $data): void {
                        $loanData = [
                            'bank_name' => $data['bank_name'],
                            'loan_type' => $data['loan_type'],
                            'amount' => floatval($data['amount']),
                            'monthly_payment' => floatval($data['monthly_payment']),
                            'installments' => intval($data['installments']),
                            'remaining_installments' => intval($data['remaining_installments']),
                            'start_date' => $data['start_date'],
                            'next_payment_date' => $data['next_payment_date'],
                            'status' => 'pending',
                            'notes' => $data['notes'],
                        ];
                        $this->loanService->createLoan($loanData);
                        Notification::make()
                            ->title('تم بنجاح!')
                            ->body('تمت إضافة القرض بنجاح.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('due_date', 'asc');
    }

    /**
     * Creates the loan form
     * 
     * @return array Form components
     */
    protected function getLoanForm(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('bank_name')
                    ->label('اسم البنك')
                    ->required(),
                Forms\Components\Select::make('loan_type')
                    ->label('نوع القرض')
                    ->options(['personal' => 'فردي', 'business' => 'تجاري'])
                    ->native(false)
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('مبلغ القرض')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('TL')
                    ->step(0.01)
                    ->live(),
                Forms\Components\TextInput::make('monthly_payment')
                    ->label('مبلغ الدفعة الشهرية')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('TL')
                    ->step(0.01),
                Forms\Components\TextInput::make('installments')
                    ->label('إجمالي عدد الأقساط')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Forms\Components\TextInput::make('remaining_installments')
                    ->label('عدد الأقساط المتبقية')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البداية')
                    ->default(now())
                    ->required()
                    ->format('d.m.Y'),
                Forms\Components\DatePicker::make('next_payment_date')
                    ->label('تاريخ الدفعة التالية')
                    ->default(now()->addMonth())
                    ->required()
                    ->format('d.m.Y'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpan(2),
            ]),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.loan.loan-manager');
    }
}