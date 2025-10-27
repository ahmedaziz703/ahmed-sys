<?php

namespace App\Livewire\Analysis;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;

/**
 * Cash Flow Analysis Component
 * 
 * This component provides a cash flow analysis and reporting.
 * Features:
 * - Cash flow analysis by date range
 * - Account-based filtering
 * - Income and expense trends
 * - Cash flow health assessment
 * - Detailed metrics (total income, expense, average, peak values)
 * - Visual graphs and tables
 * 
 * @package App\Livewire\AI
 */
class CashFlowAnalysis extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var string Start date (Y-m-d format) */
    public $startDate;

    /** @var string End date (Y-m-d format) */
    public $endDate;

    /** @var string Analysis period (daily, weekly, monthly, quarterly, yearly) */
    public $period = 'monthly';

    /** @var array Selected account IDs */
    public $accountIds = [];
    
    /** @var string Chart type (line, bar, stacked) */
    public $chartType = 'line';

    /** @var string Chart start date (Y-m-d format) */
    public $chartStartDate;

    /** @var string Chart end date (Y-m-d format) */
    public $chartEndDate;

    /** @var string Chart period (daily, weekly, monthly, quarterly, yearly) */
    public $chartPeriod = 'monthly';

    /** @var array Chart data */
    public $chartData = [];
    
    /** @var array Cash flow data */
    public $cashFlowData = [];

    /** @var float Net cash flow */
    public $netCashFlow = 0;

    /** @var float Total income */
    public $totalInflow = 0;

    /** @var float Total expense */
    public $totalOutflow = 0;

    /** @var float Average income */
    public $averageInflow = 0;

    /** @var float Average expense */
    public $averageOutflow = 0;

    /** @var float Cumulative cash flow */
    public $cumulativeCashFlow = 0;

    /** @var float Highest income */
    public $peakInflow = 0;

    /** @var float Highest expense */
    public $peakOutflow = 0;

    /** @var array Cash flow summary */
    public $cashFlowSummary = [];
    
    /** @var string|null Error message */
    public $errorMessage = null;

    /**
     * When the component is mounted, it loads the default dates and initial data
     * Loads the default dates and initial data
     */
    public function mount(): void
    {
        // Set initial default dates
        $this->resetDatesToDefault(); 
        $this->period = 'monthly'; 
        $this->chartPeriod = 'monthly';
        
        // Initial data load
        $this->loadData();
    }

    /**
     * Resets all dates to the default values (last 3 months)
     */
    private function resetDatesToDefault(): void
    {
        $defaultStartDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
        $defaultEndDate = Carbon::now()->format('Y-m-d');
        $this->startDate = $defaultStartDate;
        $this->endDate = $defaultEndDate;
        $this->chartStartDate = $defaultStartDate;
        $this->chartEndDate = $defaultEndDate;
        \Log::info('Dates reset to default:', ['start' => $this->startDate, 'end' => $this->endDate]);
    }
    
    /**
     * Checks the validity of the dates
     * 
     * @return bool True if dates are valid, false otherwise
     */
    private function validateDates(): bool
    {
        $this->errorMessage = null; 
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $now = Carbon::now();

            if ($startDate->isAfter($now)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية في المستقبل.';
                return false;
            }
            if ($endDate->isAfter($now)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ النهاية في المستقبل.';
                return false;
            }
            if ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية بعد تاريخ النهاية.';
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz تاريخ formatı. Lütfen geçerli bir تاريخ girin.';
            return false;
        }
    }
    
    /**
     * Loads all data and calculates the metrics
     * Checks the validity of the dates and resets to default if invalid
     */
    private function loadData(): void
    {
        // Validate dates and reset to default if invalid before loading data
        if (!$this->validateDates()) {
             $this->resetDatesToDefault();
             // Error message is already set by validateDates
        }
        
        \Log::info('Loading data with dates:', ['start' => $this->startDate, 'end' => $this->endDate, 'chartStart' => $this->chartStartDate, 'chartEnd' => $this->chartEndDate]);

        // Load data using the validated (or reset) dates
        $this->loadGeneralCashFlowData();
        $this->loadFilteredCashFlowData();
        $this->loadChartData();
    }
    
    /**
     * Loads the general cash flow data (without account filtering)
     */
    private function loadGeneralCashFlowData()
    {
        $this->generalCashFlowData = $this->getCashFlowData($this->startDate, $this->endDate, $this->period, []);
        
        // Calculate the general cash flow metrics
        $this->generalNetCashFlow = $this->calculateNetCashFlow($this->generalCashFlowData);
        $this->generalAverageInflow = $this->calculateAverageInflow($this->generalCashFlowData);
        $this->generalAverageOutflow = $this->calculateAverageOutflow($this->generalCashFlowData);
        
        // Cash flow health and summary
        $this->cashFlowSummary = $this->generateCashFlowSummary($this->generalCashFlowData);
    }
    
    /**
     * Loads the filtered cash flow data (based on selected accounts)
     */
    private function loadFilteredCashFlowData()
    {
        $this->cashFlowData = $this->getCashFlowData($this->startDate, $this->endDate, $this->period, $this->accountIds);
        
        // Calculate the filtered cash flow metrics
        $this->netCashFlow = $this->calculateNetCashFlow($this->cashFlowData);
        $this->totalInflow = $this->cashFlowData->sum('inflow');
        $this->totalOutflow = $this->cashFlowData->sum('outflow');
        $this->cumulativeCashFlow = $this->calculateCumulativeCashFlow($this->cashFlowData);
        $this->averageInflow = $this->calculateAverageInflow($this->cashFlowData);
        $this->averageOutflow = $this->calculateAverageOutflow($this->cashFlowData);
        $this->peakInflow = $this->calculatePeakInflow($this->cashFlowData);
        $this->peakOutflow = $this->calculatePeakOutflow($this->cashFlowData);
    }
    
    /**
     * Loads the chart data (using separate date range and period)
     */
    private function loadChartData()
    {
        // Ensure chart dates are initialized if somehow empty
        if (empty($this->chartStartDate) || empty($this->chartEndDate)) {
             $this->resetDatesToDefault();
        }
        
        $chartDataResult = $this->getCashFlowData($this->chartStartDate, $this->chartEndDate, $this->chartPeriod, $this->accountIds);
        
        $this->chartData = [
            'labels' => $chartDataResult->pluck('period')->toArray(),
            'inflowData' => $chartDataResult->pluck('inflow')->toArray(),
            'outflowData' => $chartDataResult->pluck('outflow')->toArray(),
            'netData' => $chartDataResult->pluck('net')->toArray(),
        ];
        \Log::info('Chart data loaded:', ['labels' => count($this->chartData['labels'])]);
    }
    
    /**
     * Updates the chart when filter changes
     * 
     * @return null
     */
    #[On('filterChanged')]
    public function updateChart()
    {
        // Called by filter changes, reload data
        $this->loadData();
        
        \Log::info('Chart updated via filterChanged event', ['chartData' => $this->chartData]);
        
        // Dispatch event to update frontend chart
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
        
        return null; 
    }
    
    /**
     * Synchronizes the chart start date when the start date is updated
     */
    public function updatedStartDate(): void
    {
        $this->chartStartDate = $this->startDate;
        $this->updatedChartStartDate(); 
    }

    /**
     * Synchronizes the chart end date when the end date is updated
     */
    public function updatedEndDate(): void
    {
       $this->chartEndDate = $this->endDate;
       $this->updatedChartEndDate();
    }

    /**
     * Updates the chart when the account selection is updated
     */
    public function updatedAccountIds()
    {
        $this->updateChart();
    }
    
    /**
     * Reloads the data when the chart start date is updated
     */
    public function updatedChartStartDate(): void
    {
        $this->errorMessage = null; 
        $isValid = true;

        try {
            $startDate = Carbon::parse($this->chartStartDate);
            $endDate = Carbon::parse($this->chartEndDate); 
            $now = Carbon::now();

            if ($startDate->isAfter($now)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية في المستقبل.';
                $isValid = false;
            } elseif ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية بعد تاريخ النهاية.';
                $isValid = false;
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz تاريخ formatı. Lütfen geçerli bir تاريخ girin.';
            $isValid = false;
        }

        if ($isValid) {
            $this->startDate = $this->chartStartDate;
        } else {
            $this->resetDatesToDefault();
        }

        $this->loadData(); 
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Reloads the data when the chart end date is updated
     */
    public function updatedChartEndDate(): void
    {
        $this->errorMessage = null;
        $isValid = true;

        try {
            $startDate = Carbon::parse($this->chartStartDate); 
            $endDate = Carbon::parse($this->chartEndDate);
            $now = Carbon::now();

            if ($endDate->isAfter($now)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ النهاية في المستقبل.';
                $isValid = false;
            } elseif ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية بعد تاريخ النهاية.';
                $isValid = false;
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz تاريخ formatı. Lütfen geçerli bir تاريخ girin.';
            $isValid = false;
        }

        if ($isValid) {
            $this->endDate = $this->chartEndDate;
        } else {
            $this->resetDatesToDefault();
        }

        $this->loadData();
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Reloads the data when the chart period is updated
     */
    public function updatedChartPeriod()
    {
        $this->loadChartData();
        $this->period = $this->chartPeriod;
        
        $this->cashFlowData = $this->getCashFlowData($this->startDate, $this->endDate, $this->period, $this->accountIds);
        
        $this->netCashFlow = $this->calculateNetCashFlow($this->cashFlowData);
        $this->cumulativeCashFlow = $this->calculateCumulativeCashFlow($this->cashFlowData);
        $this->averageInflow = $this->calculateAverageInflow($this->cashFlowData);
        $this->averageOutflow = $this->calculateAverageOutflow($this->cashFlowData);
        $this->peakInflow = $this->calculatePeakInflow($this->cashFlowData);
        $this->peakOutflow = $this->calculatePeakOutflow($this->cashFlowData);
        
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Resets the chart filters to the default values
     */
    public function resetChartFilters()
    {
        $this->chartStartDate = $this->startDate;
        $this->chartEndDate = $this->endDate;
        $this->chartPeriod = $this->period;
        
        $this->loadChartData();
        
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Reloads the data when the chart type is updated
     * 
     * @param string $value جديد grafik tipi
     */
    public function updatedChartType($value)
    {
        $this->chartType = $value;
        $this->loadData();
        
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.analysis.cash-flow', [
            'cashFlowData' => $this->cashFlowData,
            'accounts' => Account::where('user_id', auth()->id())->where('status', true)->get(),
            'netCashFlow' => $this->netCashFlow,
            'cumulativeCashFlow' => $this->cumulativeCashFlow,
            'totalInflow' => $this->totalInflow,
            'totalOutflow' => $this->totalOutflow,
            'averageInflow' => $this->averageInflow,
            'averageOutflow' => $this->averageOutflow,
            'peakInflow' => $this->peakInflow,
            'peakOutflow' => $this->peakOutflow,
            'cashFlowSummary' => $this->cashFlowSummary,
        ]);
    }
    
    /**
     * Creates the form schema
     * 
     * @return array Form components
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Filtreler')
                ->description('نقدًا akışı analizini filtrelemek için aşağıdaki seçenekleri kullanın')
                ->collapsible()
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('startDate')
                            ->label('تاريخ البداية')
                            ->displayFormat('d.m.Y')
                            ->native(false)
                            ->required(),
                        
                        Forms\Components\DatePicker::make('endDate')
                            ->label('تاريخ النهاية')
                            ->displayFormat('d.m.Y')
                            ->native(false)
                            ->required(),
                    ]),
                    
                    Forms\Components\Grid::make(1)->schema([
                        Forms\Components\MultiSelect::make('accountIds')
                            ->label('حسابlar')
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->options(function () {
                                return Account::where('user_id', auth()->id())
                                    ->where('status', true)
                                    ->where('type', '!=', 'cash') // Filter out cash accounts
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),
                    ]),
                ])
                ->columns(1),
        ];
    }
    
    /**
     * Gets the cash flow data
     * 
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @param string|null $period Period (daily, weekly, monthly, quarterly, yearly)
     * @param array|null $accountIds Account IDs
     * @return \Illuminate\Support\Collection
     */
    public function getCashFlowData($startDate = null, $endDate = null, $period = null, $accountIds = null)
    {
        // Use default values
        $startDate = $startDate ?? $this->startDate;
        $endDate = $endDate ?? $this->endDate;
        $period = $period ?? $this->period;
        $accountIds = $accountIds ?? $this->accountIds;
        
        $query = Transaction::query()
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('type', 'income')
                    ->orWhere('type', 'expense');
            });
        
        if (!empty($accountIds)) {
            $query->where(function ($query) use ($accountIds) {
                $query->whereIn('source_account_id', $accountIds)
                    ->orWhereIn('destination_account_id', $accountIds);
            });
        }
        
        // Create the SQL query based on the period
        if ($period === 'quarterly') {
            // Use subquery to resolve the quarterly query
            $sql = "SELECT 
                    t.year,
                    t.quarter,
                    CONCAT(t.year, '-Q', t.quarter) as period,
                    SUM(t.income) as inflow,
                    SUM(t.expense) as outflow
                FROM (
                    SELECT 
                        YEAR(date) as year,
                        QUARTER(date) as quarter,
                        CASE WHEN type = 'income' THEN try_equivalent ELSE 0 END as income,
                        CASE WHEN type = 'expense' THEN try_equivalent ELSE 0 END as expense
                    FROM transactions
                    WHERE user_id = ? AND date BETWEEN ? AND ? AND (type = 'income' OR type = 'expense') AND deleted_at IS NULL
                ) as t
                GROUP BY t.year, t.quarter
                ORDER BY t.year, t.quarter";
            
            $bindings = [
                auth()->id(),
                $startDate,
                $endDate
            ];
            
            if (!empty($accountIds)) {
                // We need to use a separate query for the account filter
                $sql = "SELECT 
                        t.year,
                        t.quarter,
                        CONCAT(t.year, '-Q', t.quarter) as period,
                        SUM(t.income) as inflow,
                        SUM(t.expense) as outflow
                    FROM (
                        SELECT 
                            YEAR(date) as year,
                            QUARTER(date) as quarter,
                            CASE WHEN type = 'income' THEN try_equivalent ELSE 0 END as income,
                            CASE WHEN type = 'expense' THEN try_equivalent ELSE 0 END as expense
                        FROM transactions
                        WHERE user_id = ? AND date BETWEEN ? AND ? AND (type = 'income' OR type = 'expense') 
                        AND (source_account_id IN (" . implode(',', array_fill(0, count($accountIds), '?')) . ") 
                            OR destination_account_id IN (" . implode(',', array_fill(0, count($accountIds), '?')) . "))
                        AND deleted_at IS NULL
                    ) as t
                    GROUP BY t.year, t.quarter
                    ORDER BY t.year, t.quarter";
                
                $bindings = [
                    auth()->id(),
                    $startDate,
                    $endDate
                ];
                
                // We need to add the account IDs twice (source and destination)
                foreach ($accountIds as $id) {
                    $bindings[] = $id;
                }
                foreach ($accountIds as $id) {
                    $bindings[] = $id;
                }
            }
            
            $rawResults = DB::select($sql, $bindings);
            
            // Convert the results to a collection
            $results = collect($rawResults)
                ->map(function ($item) {
                    $item->net = $item->inflow - $item->outflow;
                    return $item;
                });
        } else {
            // For other periods, use the normal query
            $dateFormat = $this->getDateFormat($period);
            
            $results = $query->select(
                    DB::raw("DATE_FORMAT(date, '{$dateFormat}') as period"),
                    DB::raw("SUM(CASE WHEN type = 'income' THEN try_equivalent ELSE 0 END) as inflow"),
                    DB::raw("SUM(CASE WHEN type = 'expense' THEN try_equivalent ELSE 0 END) as outflow")
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    $item->net = $item->inflow - $item->outflow;
                    return $item;
                });
        }
        
        return $results;
    }
    
    /**
     * Returns the date format based on the period
     * 
     * @param string|null $period Period (daily, weekly, monthly, quarterly, yearly)
     * @return string MySQL DATE_FORMAT format string
     */
    private function getDateFormat($period = null)
    {
        $period = $period ?? $this->period;
        
        switch ($period) {
            case 'daily':
                return '%Y-%m-%d'; // Daily: 2023-01-01
            case 'weekly':
                return '%x-W%v'; // Weekly: 2023-W01
            case 'monthly':
                return '%Y-%m'; // Monthly: 2023-01
            case 'quarterly':
                return '%Y-Q' . DB::raw('QUARTER(date)'); // Quarterly: 2023-Q1
            case 'yearly':
                return '%Y'; // Yearly: 2025
            default:
                return '%Y-%m'; // Default is monthly
        }
    }
    
    /**
     * Calculates the net cash flow
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return float Net cash flow
     */
    private function calculateNetCashFlow($data)
    {
        $sum = $data->sum('net');
        \Log::info('Calculated Net Cash Flow: ' . $sum);
        return $sum;
    }
    
    /**
     * Calculates the cumulative cash flow
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return array Cumulative cash flow data
     */
    private function calculateCumulativeCashFlow($data)
    {
        $cumulative = [];
        $runningTotal = 0;
        
        foreach ($data as $item) {
            $runningTotal += $item->net;
            $cumulative[] = [
                'period' => $item->period,
                'value' => $runningTotal
            ];
        }
        
        return $cumulative;
    }
    
    /**
     * Calculates the average inflow
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return float Average inflow
     */
    private function calculateAverageInflow($data)
    {
        $totalInflow = $data->sum('inflow');
        
        if ($data->count() === 0) {
            return 0;
        }
        
        return $totalInflow / $data->count();
    }
    
    /**
     * Calculates the average outflow
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return float Average outflow
     */
    private function calculateAverageOutflow($data)
    {
        $totalOutflow = $data->sum('outflow');
        
        if ($data->count() === 0) {
            return 0;
        }
        
        return $totalOutflow / $data->count();
    }
    
    /**
     * Calculates the peak inflow
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return float Peak inflow
     */
    private function calculatePeakInflow($data)
    {
        return $data->max('inflow');
    }
    
    /**
     * Calculates the peak outflow
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return float Peak outflow
     */
    private function calculatePeakOutflow($data)
    {
        return $data->max('outflow');
    }
    
    /**
     * Generates the cash flow summary
     * 
     * @param \Illuminate\Support\Collection $data Cash flow data
     * @return array Cash flow summary
     */
    private function generateCashFlowSummary($data)
    {
        $totalInflow = $data->sum('inflow');
        $totalOutflow = $data->sum('outflow');
        $netCashFlow = $totalInflow - $totalOutflow;
        
        // Cash flow ratio
        $cashFlowRatio = $totalOutflow > 0 ? $totalInflow / $totalOutflow : ($totalInflow > 0 ? 2 : 0);
        
        // Trend percentage
        $trendPercent = 0;
        
        if ($data->count() >= 2) {
            $firstPeriod = $data->first();
            $lastPeriod = $data->last();
            
            if ($firstPeriod && $lastPeriod && $firstPeriod->net != 0) {
                $trendPercent = (($lastPeriod->net - $firstPeriod->net) / abs($firstPeriod->net)) * 100;
            }
        }
        
        $summary = [
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $netCashFlow,
            'cashFlowRatio' => $cashFlowRatio,
            'trendPercent' => $trendPercent,
        ];
        
        // Cash flow health evaluation
        if ($netCashFlow > 0 && $cashFlowRatio >= 1.2) {
            $summary['health'] = 'excellent';
            $summary['healthMessage'] = 'التدفق النقدي في الحالة ممتاز. لديك دخل كافٍ لتغطية حساباتك ويمكنك التوفير';
        } elseif ($netCashFlow > 0 && $cashFlowRatio >= 1.1) {
            $summary['health'] = 'good';
            $summary['healthMessage'] = 'التدفق النقدي جيد. لديك دخل كافٍ لتغطية حساباتك';
        } elseif ($netCashFlow >= 0) {
            $summary['health'] = 'adequate';
            $summary['healthMessage'] = 'التدفق النقدي كافٍ لكن هناك فرص لتحسينه. يمكنك تغطية حساباتك، لكن المتبقي للتوفير قليل';
        } elseif ($netCashFlow < 0 && $trendPercent > 0) {
            $summary['health'] = 'improving';
            $summary['healthMessage'] = 'لتدفق النقدي سلبي لكنه يتحسن. الاتجاه العام إيجابي';
        } elseif ($netCashFlow < 0 && $cashFlowRatio >= 0.8) {
            $summary['health'] = 'warning';
            $summary['healthMessage'] = 'التدفق النقدي يتطلب الحذر. حساباتك تتجاوز دخلك';
        } else {
            $summary['health'] = 'critical';
            $summary['healthMessage'] = 'التدفق النقدي في مستوى حرج. حساباتك تتجاوز دخلك. يجب اتخاذ إجراءات عاجلة';
        }
        
        // Recommendations
        $recommendations = [];
        
        if ($cashFlowRatio < 1.0) {
            $recommendations[] = 'أنشئ خطة ميزانية لتقليل مصاريفك';
            $recommendations[] = 'راجع الاشتراكات غير الضرورية والمدفوعات المتكررة لديك';
            $recommendations[] = 'ابحث عن مصادر دخل إضافية. مؤخراً، التدفق النقدي لديك يقل، فكر في تنويع مصادر دخلك';
        }
        
        if ($data->count() >= 3) {
            $lastThree = $data->take(-3);
            $trend = $lastThree->last()->net - $lastThree->first()->net;
            
            if ($trend < 0) {
                $recommendations[] = 'في الفترة الأخيرة، التدفق النقدي لديك يتناقص. فكر في تنويع مصادر دخلك';
            }
        }
        
        $summary['recommendations'] = $recommendations;
        
        return $summary;
    }
}
