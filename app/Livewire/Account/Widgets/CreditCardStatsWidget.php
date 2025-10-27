<?php

namespace App\Livewire\Account\Widgets;

use App\Models\Account;
use App\Models\Transaction;
use Filament\Widgets\Widget as BaseWidget;
use Carbon\Carbon;
use App\Enums\PaymentMethodEnum;

/**
 * Credit Card Stats Widget
 * 
 * Widget to display important statistics related to credit cards.
 * Lists important metrics such as total limit, debt, upcoming installments, and other important metrics.
 */
class CreditCardStatsWidget extends BaseWidget
{
    protected static string $view = 'livewire.account.widgets.credit-card-stats-widget';

    protected $listeners = [
        'creditCardCreated' => '$refresh',
        'creditCardUpdated' => '$refresh',
        'creditCardDeleted' => '$refresh',
        'transactionCreated' => '$refresh',
        'transactionUpdated' => '$refresh',
        'transactionDeleted' => '$refresh'
    ];

    /**
     * Calculate and return statistics
     * 
     * @return array Statistics array
     */
    public function getStats(): array
    {
        $totalLimit = $this->getTotalLimit();
        $totalBalance = $this->getTotalBalance();
        $minimumPayment = $this->getTotalMinimumPayment();

        return [
            [
                'label' => 'الاجمالي من الحد الاقصى',
                'value' => 'ر.ي' . number_format($totalLimit, 2, ',', '.'),
                'icon' => 'heroicon-o-credit-card',
                'color' => 'info',
            ],
            [
                'label' => 'إجمالي الديون',
                'value' => 'ر.ي' . number_format($totalBalance, 2, ',', '.'),
                'icon' => 'heroicon-o-banknotes',
                'color' => $totalBalance > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'الأقساط القادمة',
                'value' => 'ر.ي' . number_format($this->getUpcomingInstallmentsTotal(), 2, ',', '.'),
                'icon' => 'heroicon-o-calendar',
                'color' => 'warning',
            ],
        ];
    }

    /**
     * Calculate and return total credit card limit
     * 
     * @return float Total limit
     */
    private function getTotalLimit(): float
    {
        return Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true) // Only active cards
            ->get()
            ->sum(function ($account) {
                return (float) ($account->details['credit_limit'] ?? 0);
            });
    }

    /**
     * Calculate and return total credit card debt
     * 
     * @return float Total debt
     */
    private function getTotalBalance(): float
    {
        // Calculate and return total credit card debt
        // Directly use the balance field
        return Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true)
            ->sum('balance');
    }

    /**
     * Calculate and return total minimum payment
     * 
     * @return float Total minimum payment
     */
    private function getTotalMinimumPayment(): float
    {
        $cards = Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true) // Only active cards
            ->get();

        $total = 0;
        $currentDate = now();

        foreach ($cards as $card) {
            $statementDay = (int) ($card->details['statement_day'] ?? 1);
            
            // Statement start and end dates
            $statementStart = $currentDate->copy()->setDay($statementDay);
            if ($currentDate->day < $statementDay) {
                $statementStart->subMonth();
            }
            $statementEnd = $statementStart->copy()->addMonth()->subDay();

            // Installments to be paid this month
            $currentInstallments = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNotNull('installments')
                ->where('remaining_installments', '>', 0)
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->try_equivalent / $transaction->installments;
                });

            // Regular expenses
            $regularExpenses = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNull('installments')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Payments made this month
            $payments = Transaction::query()
                ->where('type', 'payment')
                ->where('destination_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Statement total
            $statementTotal = $currentInstallments + $regularExpenses;

            if ($statementTotal > 0) {
                $minimumPayment = $statementTotal * ($statementTotal >= 50000 ? 0.40 : 0.20);
                // Remaining minimum amount after payments
                $remainingMinimum = max(0, $minimumPayment - $payments);
                $total += $remainingMinimum;
            }
        }

        return $total;
    }

    private function getCurrentMonthExpense(): float
    {
        return Transaction::query()
            ->where('type', 'expense')
            ->whereNull('deleted_at')
            ->whereHas('sourceAccount', function ($query) {
                $query->where('type', Account::TYPE_CREDIT_CARD)
                    ->whereNull('deleted_at')
                    ->where('status', true); // Only active cards
            })
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->sum('try_equivalent');
    }

    private function getCurrentStatementPayment(): float
    {
        $cards = Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true) // Only active cards
            ->get();

        $total = 0;
        $currentDate = now();

        foreach ($cards as $card) {
            $statementDay = (int) ($card->details['statement_day'] ?? 1);
            
            // Statement start and end dates
            $statementStart = $currentDate->copy()->setDay($statementDay);
            if ($currentDate->day < $statementDay) {
                $statementStart->subMonth();
            }
            $statementEnd = $statementStart->copy()->addMonth()->subDay();

            // Installments to be paid this month
            $currentInstallments = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNotNull('installments')
                ->where('remaining_installments', '>', 0)
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->try_equivalent / $transaction->installments;
                });

            // Regular expenses
            $regularExpenses = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNull('installments')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Payments made this month
            $payments = Transaction::query()
                ->where('type', 'payment')
                ->where('destination_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Statement total (after payments)
            $statementTotal = $currentInstallments + $regularExpenses - $payments;
            $total += max(0, $statementTotal);
        }

        return $total;
    }

    private function getCurrentMonthPayment(): float
    {
        return Transaction::query()
            ->where('type', 'payment')
            ->whereNull('deleted_at')
            ->whereHas('destinationAccount', function ($query) {
                $query->where('type', Account::TYPE_CREDIT_CARD)
                    ->whereNull('deleted_at')
                    ->where('status', true); // Only active cards
            })
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->sum('try_equivalent');
    }

    /**
     * Calculate and return total upcoming installments
     * 
     * @return float Total upcoming installments
     */
    private function getUpcomingInstallmentsTotal(): float
    {
        // Installment transactions
        $installmentTransactions = Transaction::query()
            ->where('type', 'expense')
            ->whereNull('deleted_at')
            ->whereHas('sourceAccount', function ($query) {
                $query->where('type', Account::TYPE_CREDIT_CARD)
                    ->whereNull('deleted_at');
            })
            ->whereNotNull('installments')
            ->where('remaining_installments', '>', 1) // At least 2 installments remaining
            ->get();

        $total = 0;

        foreach ($installmentTransactions as $transaction) {
            // Remaining installments after the first installment
            $remainingAmount = ($transaction->remaining_installments - 1) * ($transaction->try_equivalent / $transaction->installments);
            $total += $remainingAmount;
        }

        return $total;
    }
}