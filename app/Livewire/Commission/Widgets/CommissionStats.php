<?php

namespace App\Livewire\Commission\Widgets;

use App\Models\Commission;
use App\Models\CommissionPayout;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

/**
 * Commission Stats Widget
 * 
 * Widget to display commission statistics.
 */
class CommissionStats extends Widget
{
    protected static string $view = 'livewire.commission.widgets.commission-stats';

    public $user;

    /**
     * Mount the component
     * 
     * @param $user User object
     * @return void
     */
    public function mount($user)
    {
        $this->user = $user;
    }

    /**
     * Refresh the component
     * 
     * @return void
     */
    #[On('commission-stats-updated')]
    public function refresh(): void
    {
        $this->dispatch('refresh');
    }

    /**
     * Get the stats
     * 
     * @return array Stats array
     */
    public function getStats(): array
    {
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');

        // This month earned commission - by transaction date
        $currentMonthCommission = Commission::query()
            ->where('user_id', $this->user->id)
            ->whereHas('transaction', function ($query) use ($now) {
                $query->whereYear('date', $now->year)
                      ->whereMonth('date', $now->month);
            })
            ->sum('commission_amount');

        // This month paid commission
        $currentMonthPayout = CommissionPayout::query()
            ->where('user_id', $this->user->id)
            ->whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount');

        // This month remaining commission
        $currentMonthRemaining = $currentMonthCommission - $currentMonthPayout;

        // Last month earned commission - by transaction date
        $lastMonthCommission = Commission::query()
            ->where('user_id', $this->user->id)
            ->whereHas('transaction', function ($query) use ($now) {
                $query->whereYear('date', $now->copy()->subMonth()->year)
                      ->whereMonth('date', $now->copy()->subMonth()->month);
            })
            ->sum('commission_amount');

        // Last month paid commission
        $lastMonthPayout = CommissionPayout::query()
            ->where('user_id', $this->user->id)
            ->whereYear('payment_date', $now->copy()->subMonth()->year)
            ->whereMonth('payment_date', $now->copy()->subMonth()->month)
            ->sum('amount');

        // Last month remaining commission
        $lastMonthRemaining = $lastMonthCommission - $lastMonthPayout;

        // Total earned commission
        $totalCommission = Commission::query()
            ->where('user_id', $this->user->id)
            ->sum('commission_amount');

        // Total paid commission
        $totalPayout = CommissionPayout::query()
            ->where('user_id', $this->user->id)
            ->sum('amount');

        // Total remaining commission
        $totalRemaining = $totalCommission - $totalPayout;

        return [
            // This month statistics
            [
                'label' => 'تم ربحه هذا الشهر ',
                'value' => number_format($currentMonthCommission, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success'
            ],
            [
                'label' => 'الدفعات المنجزة هذا الشهر',
                'value' => number_format($currentMonthPayout, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'success'
            ],
            [
                'label' => 'الدفعات المتبقية هذا الشهر',
                'value' => number_format($currentMonthRemaining, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-calculator',
                'color' => 'success'
            ],
            
            // Last month statistics
            [
                'label' => 'تم ربحه الشهر الماضي',
                'value' => number_format($lastMonthCommission, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'warning'
            ],
            [
                'label' => 'الدفعات المنجزة الشهر الماضي',
                'value' => number_format($lastMonthPayout, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'warning'
            ],
            [
                'label' => 'الدفعات المتبقية الشهر الماضي',
                'value' => number_format($lastMonthRemaining, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-calculator',
                'color' => 'warning'
            ],
            
            // Total statistics
            [
                'label' => 'اجمالي المبلغ المكتسب',
                'value' => number_format($totalCommission, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'primary'
            ],
            [
                'label' => 'إجمالي الدفعات المنجزة',
                'value' => number_format($totalPayout, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'primary'
            ],
            [
                'label' => 'إجمالي الدفعات المتبقية',
                'value' => number_format($totalRemaining, 2, ',', '.') . ' ر.ي',
                'icon' => 'heroicon-o-calculator',
                'color' => 'primary'
            ]
        ];
    }
} 