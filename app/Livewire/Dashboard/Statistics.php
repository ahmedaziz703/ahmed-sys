<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Services\Statistics\Contracts\StatisticsServiceInterface;
use Illuminate\Contracts\View\View;

/**
 * Statistics Component
 * 
 * This component provides functionality to display statistics on the dashboard.
 * Features:
 * - Display statistics
 */ 
final class Statistics extends Component
{
    protected $listeners = ['transaction-updated' => '$refresh'];
    
    /** @var StatisticsServiceInterface Statistics service */
    private StatisticsServiceInterface $statisticsService;
    
    /**
     * When the component is booted, the statistics service is injected
     * 
     * @param StatisticsServiceInterface $statisticsService Statistics service
     * @return void
     */
    public function boot(StatisticsServiceInterface $statisticsService): void
    {
        $this->statisticsService = $statisticsService;
    }
    
    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        $statistics = $this->statisticsService->getDashboardStatistics();
        return view('livewire.dashboard.statistics', [
            'statistics' => $statistics
        ]);
    }
} 