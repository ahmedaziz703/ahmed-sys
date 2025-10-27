<?php

declare(strict_types=1);

namespace App\Livewire\Debt\Widgets;

use App\Models\Debt;
use Filament\Widgets\Widget as BaseWidget;
use Carbon\Carbon;
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\Models\Transaction;

/**
 * Debt/Receivable Stats Widget
 * 
 * This widget displays debt and receivable statistics.
 * Features:
 * - Total debt amount
 * - Total receivable amount
 * - Overdue debt amount
 * - Overdue receivable amount
 * - Monthly collections amount
 * - Automatic status updates
 * 
 * @package App\Livewire\Debt\Widgets
 */

final class DebtStatsWidget extends BaseWidget
{
    /** @var string Widget view file */
    protected static string $view = 'livewire.debt.widgets.debt-stats-widget';

    /** @var DebtServiceInterface Debt/receivable service */
    private DebtServiceInterface $debtService;

    /**
     * When the widget is booted, the debt/receivable service is injected
     * 
     * @param DebtServiceInterface $debtService Debt/receivable service
     * @return void
     */
    public function boot(DebtServiceInterface $debtService): void
    {
        $this->debtService = $debtService;
    }

    /**
     * Widget listeners
     * 
     * @return array Widget listeners
     */
    protected function getListeners(): array
    {
        return [
            'debt-created' => '$refresh',
            'debt-updated' => '$refresh',
            'debt-deleted' => '$refresh',
        ];
    }

    /**
     * Get statistics data
     * 
     * @return array Statistics data
     */
    public function getStats(): array
    {
        Transaction::where('next_payment_date', '<', now()->startOfDay())
            ->where('status', 'pending')
            ->whereIn('type', ['loan_payment', 'debt_payment'])
            ->each(function (Transaction $transaction) {
                $transaction->update(['status' => 'overdue']);
            });

        return [
            [
                'label' => 'المبلغ المستحق',
                'value' => '$' . number_format($this->getTotalDebts(), 2, ',', '.'),
                'icon' => 'heroicon-o-arrow-up-circle',
                'color' => 'danger',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Alacak',
                'value' => '$' . number_format($this->getTotalReceivables(), 2, ',', '.'),
                'icon' => 'heroicon-o-arrow-down-circle',
                'color' => 'success',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Geciken Borç',
                'value' => '$' . number_format($this->getOverduePayable(), 2, ',', '.'),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'warning',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Geciken Alacak',
                'value' => '$' . number_format($this->getOverdueReceivable(), 2, ',', '.'),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'warning',
                'columnSpan' => 2,
            ],
            [
                'label' => 'المدفوعات المستلمة',
                'value' => '$' . number_format($this->getCurrentMonthCollections(), 2, ',', '.'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'primary',
                'columnSpan' => 2,
            ],
        ];
    }

    /**
     * Get overdue debt amount
     * 
     * @return float Overdue debt amount
     */
    private function getOverduePayable(): float
    {
        return (float) Transaction::where('next_payment_date', '<', now()->startOfDay())
            ->where('status', '!=', 'completed')
            ->where('type', 'loan_payment')
            ->sum('amount');
    }
    
    /**
     * Get overdue receivable amount
     * 
     * @return float Overdue receivable amount
     */
    private function getOverdueReceivable(): float
    {
        return (float) Transaction::where('next_payment_date', '<', now()->startOfDay())
            ->where('status', '!=', 'completed')
            ->where('type', 'debt_payment')
            ->sum('amount');
    }

    /**
     * Get current month collections amount
     * 
     * @return float Current month collections amount
     */
    private function getCurrentMonthCollections(): float
    {
        return (float) Transaction::where('type', 'debt_payment')
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get total debt amount
     * 
     * @return float Total debt amount
     */
    private function getTotalDebts(): float
    {
        return (float) Transaction::where('status', '!=', 'completed')
            ->where('type', 'loan_payment')
            ->sum('amount');
    }

    /**
     * Get total receivable amount
     * 
     * @return float Total receivable amount
     */
    private function getTotalReceivables(): float
    {
        return (float) Transaction::where('status', '!=', 'completed')
            ->where('type', 'debt_payment')
            ->sum('amount');
    }
}