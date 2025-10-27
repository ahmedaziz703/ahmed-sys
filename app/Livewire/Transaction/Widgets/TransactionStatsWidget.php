<?php

namespace App\Livewire\Transaction\Widgets;

use App\Models\Transaction;
use Filament\Widgets\Widget as BaseWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Transaction Stats Widget Component
 * 
 * This component provides functionality to display transaction statistics.
 * Features:
 * - Daily income and expense statistics
 * - Monthly income and expense statistics
 * - Increment/decrement rates compared to previous periods
 * - Automatic refresh support
 * - Error management and logging
 * 
 * @package App\Livewire\Transaction\Widgets
 */

class TransactionStatsWidget extends BaseWidget
{
    /** @var string Widget view file */
    protected static string $view = 'livewire.transaction.widgets.transaction-stats-widget';

    /** @var array Listeners */
    protected $listeners = [
        'transactionCreated' => '$refresh',
        'transactionUpdated' => '$refresh',
        'transactionDeleted' => '$refresh'
    ];

    /**
     * Returns the statistics data
     * 
     * @return array Statistics data
     */
    public function getStats(): array
    {
        try {
            return [
                [
                    'label' => 'الدخل (اليوم)',
                    'value' => 'ر.ي' . number_format($this->getTodayIncome(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => $this->getYesterdayIncome() == 0 ? 'up' : ($this->getTodayIncome() > $this->getYesterdayIncome() ? 'up' : 'down'),
                    'description' => $this->getTodayIncrement('income'),
                ],
                [
                    'label' => 'المصروف (اليوم)',
                    'value' => 'ر.ي' . number_format($this->getTodayExpense(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => $this->getYesterdayExpense() == 0 ? 'up' : ($this->getTodayExpense() > $this->getYesterdayExpense() ? 'up' : 'down'),
                    'description' => $this->getTodayIncrement('expense'),
                ],
                [
                    'label' => 'الدخل (هذا الشهر)',
                    'value' => 'ر.ي' . number_format($this->getCurrentMonthIncome(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => $this->getLastMonthIncome() == 0 ? 'up' : ($this->getCurrentMonthIncome() > $this->getLastMonthIncome() ? 'up' : 'down'),
                    'description' => $this->getCurrentMonthIncrement('income'),
                ],
                [
                    'label' => 'المصروف (هذا الشهر)',
                    'value' => 'ر.ي' . number_format($this->getCurrentMonthExpense(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => $this->getLastMonthExpense() == 0 ? 'up' : ($this->getCurrentMonthExpense() > $this->getLastMonthExpense() ? 'up' : 'down'),
                    'description' => $this->getCurrentMonthIncrement('expense'),
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Error generating transaction stats: ' . $e->getMessage());
            return [
                [
                    'label' => 'الدخل (اليوم)',
                    'value' => 'ر.ي0,00',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => 'up',
                    'description' => '-',
                ],
                [
                    'label' => 'المصروف (اليوم)',
                    'value' => 'ر.ي0,00',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => 'up',
                    'description' => '-',
                ],
                [
                    'label' => 'الدخل (هذا الشهر)',
                    'value' => 'ر.ي0,00',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => 'up',
                    'description' => '-',
                ],
                [
                    'label' => 'المصروف (هذا الشهر)',
                    'value' => 'ر.ي0,00',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => 'up',
                    'description' => '-',
                ],
            ];
        }
    }

    /**
     * Returns the today's income total
     * 
     * @return float Today's income total
     */
    private function getTodayIncome(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'income')
                ->whereDate('date', Carbon::today())
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching today income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns the today's expense total
     * 
     * @return float Today's expense total
     */
    private function getTodayExpense(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'expense')
                ->whereDate('date', Carbon::today())
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching today expense: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns the current month's income total
     * 
     * @return float Current month's income total
     */
    private function getCurrentMonthIncome(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'income')
                ->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month)
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching current month income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns the current month's expense total
     * 
     * @return float Current month's expense total
     */
    private function getCurrentMonthExpense(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'expense')
                ->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month)
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching current month expense: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculates the today's increment/decrement rate
     * 
     * @param string $type Transaction type (income/expense)
     * @return string Increment/decrement rate and direction
     */
    private function getTodayIncrement($type): string
    {
        try {
            if ($type === 'income') {
                $today = $this->getTodayIncome();
                $yesterday = $this->getYesterdayIncome();
            } else {
                $today = $this->getTodayExpense();
                $yesterday = $this->getYesterdayExpense();
            }
            
            if ($yesterday == 0) return 'اليوم الأول';
            
            $percentage = (($today - $yesterday) / abs($yesterday)) * 100;
            
            return number_format(abs($percentage), 1, ',', '.') . '% ' . 
                ($percentage >= 0 ? 'إيجابي' : 'سلبي');
        } catch (\Exception $e) {
            \Log::error('Error calculating today increment: ' . $e->getMessage());
            return 'غير محسوب';
        }
    }

    /**
     * Calculates the current month's increment/decrement rate
     * 
     * @param string $type Transaction type (income/expense)
     * @return string Increment/decrement rate and direction
     */
    private function getCurrentMonthIncrement($type): string
    {
        try {
            if ($type === 'income') {
                $currentMonth = $this->getCurrentMonthIncome();
                $lastMonth = $this->getLastMonthIncome();
            } else {
                $currentMonth = $this->getCurrentMonthExpense();
                $lastMonth = $this->getLastMonthExpense();
            }
            
            if ($lastMonth == 0) return 'الشهر الأول';
            
            $percentage = (($currentMonth - $lastMonth) / abs($lastMonth)) * 100;
            
            return number_format(abs($percentage), 1, ',', '.') . '% ' . 
                ($percentage >= 0 ? 'إيجابي' : 'سلبي');
        } catch (\Exception $e) {
            \Log::error('Error calculating month increment: ' . $e->getMessage());
            return 'غير محسوب';
        }
    }

    /**
     * Returns the yesterday's income total
     * 
     * @return float Yesterday's income total
     */
    private function getYesterdayIncome(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'income')
                ->whereDate('date', Carbon::yesterday())
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching yesterday income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns the yesterday's expense total
     * 
     * @return float Yesterday's expense total
     */
    private function getYesterdayExpense(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'expense')
                ->whereDate('date', Carbon::yesterday())
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching yesterday expense: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns the last month's income total
     * 
     * @return float Last month's income total
     */
    private function getLastMonthIncome(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'income')
                ->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month)
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching last month income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns the last month's expense total
     * 
     * @return float Last month's expense total
     */
    private function getLastMonthExpense(): float
    {
        try {
            return Transaction::query()
                ->where('type', 'expense')
                ->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month)
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching last month expense: ' . $e->getMessage());
            return 0;
        }
    }
} 