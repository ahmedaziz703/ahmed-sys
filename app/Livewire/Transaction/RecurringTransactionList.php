<?php

declare(strict_types=1);

namespace App\Livewire\Transaction;

use App\Models\Transaction;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Recurring Transaction List Component
 * 
 * This component provides functionality to manage recurring transactions.
 * Features:
 * - List recurring transactions
 * - Create recurring transactions
 * - Edit recurring transactions
 * - Delete recurring transactions
 */
class RecurringTransactionList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected SubscriptionTransactionServiceInterface $subscriptionService;

    /**
     * When the component is booted, the subscription service is injected
     * 
     * @param SubscriptionTransactionServiceInterface $subscriptionService Subscription service
     * @return void
     */
    public function boot(SubscriptionTransactionServiceInterface $subscriptionService): void
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * For Filament Action, retrieve the Transaction object
     * 
     * @param Transaction $record Transaction object
     * @return void
     */
    public function endSubscriptionAction(Transaction $record): void
    {
        try {
            $this->subscriptionService->endSubscription($record);
            Notification::make()
                ->title('تم إنهاء الاشتراك بنجاح.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('خطأ أثناء إنهاء الاشتراك: ' . $e->getMessage())
                ->danger()
                ->send();
            Log::error('خطأ أثناء إنهاء الاشتراك: ' . $e->getMessage(), ['transaction_id' => $record->id]);
        }
    }

    /**
     * Filament Table definition
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Transaction::query()
                    // ->where('user_id', auth()->id()) 
                    ->where('is_subscription', true) // Only get recurring transactions
            )
            ->emptyStateHeading(' العمليه لم يتم العثور عليها')
            ->emptyStateDescription('انشاء عمليه جديدة')
            ->columns([

                // Transaction Type Column
                Tables\Columns\BadgeColumn::make('type')
                    ->label('يكتب العملية')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'إيراد',
                        'expense' => 'إنفاق',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_badge')
                    ->label('Kalan Süre')
                    ->getStateUsing(function (Transaction $record): string { 
                        if (!$record->next_payment_date) return '-';
                        $nextPaymentDate = Carbon::parse($record->next_payment_date);
                        $now = Carbon::today();
                        $diff = $now->diffInDays($nextPaymentDate, false);

                        if ($diff < 0) {
                            return $diff . ' يوم';
                        } elseif ($diff === 0) {
                            return 'اليوم';
                        } else {
                            return $diff . ' يوم';
                        }
                    })
                    ->color(function (Transaction $record): string { // Calculate badge color
                        if (!$record->next_payment_date) return 'secondary';
                        $nextPaymentDate = Carbon::parse($record->next_payment_date);
                        $now = Carbon::today();
                        if ($nextPaymentDate->isPast() && !$nextPaymentDate->isToday()) {
                            return 'danger'; // Past
                        } elseif ($nextPaymentDate->isToday()) {
                            return 'warning'; // Today
                        }
                        return 'primary'; // Future
                    })
                    // Sort by next_payment_date
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('next_payment_date', $direction)),

                Tables\Columns\TextColumn::make('subscription_period')
                    ->label('Periyot')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'daily' => 'يومي',
                        'weekly' => 'أسبوعي',
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'biannually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                        default => '-',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money(fn (Transaction $record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('فئة')
                    ->searchable()
                    ->placeholder('لا يوجد')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_payment_date')
                    ->label('الدفعة التالية')
                    ->date('d.m.Y')
                    ->sortable(),


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('فئة')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subscription_period')
                    ->label('الفترة')
                    ->options([
                        'daily' => 'يومي',
                        'weekly' => 'أسبوعي',
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'biannually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                    ])
                    ->native(false),
            ])
            ->actions([
                Action::make('quickCreate')
                    ->label('عملية سريعة')
                    ->icon('heroicon-o-bolt')
                    ->color('info')
                    ->action(function (Transaction $record): void {
                        $this->redirectRoute('admin.transactions.create', ['copy_from' => $record->id], navigate: true);
                    })
                    ->visible(auth()->user()->can('recurring_transactions.copy')),

                Action::make('endSubscription')
                    ->label('إنهاء')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إنهاء الاشتراك')
                    ->modalDescription('هل أنت متأكد من إنهاء الاشتراك؟ لا يمكن التراجع عن هذه العملية.')
                    ->action(fn (Transaction $record) => $this->endSubscriptionAction($record)) // Action metodunu çağır
                    ->visible(auth()->user()->can('recurring_transactions.complete')),
            ])
            ->bulkActions([
            ])
            ->defaultSort('next_payment_date', 'asc')
            ->striped();
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.transaction.recurring-transaction-list-container');
    }
}