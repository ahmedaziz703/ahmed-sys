<?php

namespace App\Livewire\Analysis;

use App\Models\Transaction;
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
 * Category Analysis Component
 * 
 * This component provides detailed analysis of income and expense categories.
 * Features:
 * - Category-based analysis by date range
 * - Separate analysis for income and expense categories
 * - Category growth and trend analysis
 * - Detection of the most frequently used categories
 * - Category-based average transaction amounts
 * 
 * @package App\Livewire\Analysis
 */
class CategoryAnalysis extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var string Start date (Y-m-d format) */
    public $startDate;

    /** @var string End date (Y-m-d format) */
    public $endDate;

    /** @var string Analysis period (monthly) */
    public $period = 'monthly';

    /** @var array Selected category IDs */
    public $selectedCategories = [];

    /** @var string Chart type (bar) */
    public $chartType = 'bar';

    /** @var string Analysis type (income/expense) */
    public $analysisType = 'income';
    
    /** @var array Category analysis data */
    public $categoryData = [];

    /** @var array Most frequently used categories */
    public $topCategories = [];

    /** @var float Total transaction amount */
    public $totalAmount = 0;

    /** @var float Average transaction amount */
    public $averageAmount = 0;

    /** @var array Category growth rates */
    public $categoryGrowth = [];

    /** @var array Category trends */
    public $categoryTrends = [];
    
    /** @var string|null Error message */
    public $errorMessage = null;

    /**
     * When the component is mounted, it loads the default dates and initial data
     * Loads the default dates and initial data
     */
    public function mount(): void
    {
        // Default is last 3 months
        $this->startDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        
        // When the component is mounted, it loads the default dates and initial data
        $this->loadData();
    }

    /**
     * Checks if the date values are valid
     * 
     * @return bool If the dates are valid, true, otherwise false
     */
    private function validateDates()
    {
        $this->errorMessage = null;
        
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $now = Carbon::now();
            
            if ($startDate->isAfter($now)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية في المستقبل.';
                $this->startDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
                return false;
            }
            
            if ($endDate->isAfter($now)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ النهاية في المستقبل.';
                $this->endDate = Carbon::now()->format('Y-m-d');
                return false;
            }
            
            if ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'لا يمكن أن يكون تاريخ البداية بعد تاريخ النهاية.';
                $this->startDate = $endDate->copy()->subMonths(3)->format('Y-m-d');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz تاريخ formatı. Lütfen geçerli bir تاريخ girin.';
            $this->startDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
            $this->endDate = Carbon::now()->format('Y-m-d');
            return false;
        }
    }

    /**
     * Loads all analysis data
     */
    private function loadData()
    {
        if (!$this->validateDates()) {
            return;
        }

        $this->loadCategoryData();
        $this->calculateMetrics();
        $this->analyzeTrends();
    }

    /**
     * Loads the category-based transaction data
     */
    private function loadCategoryData()
    {
        $query = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$this->startDate, $this->endDate]);

        if ($this->analysisType === 'income') {
            $query->where('categories.type', 'income');
        } else {
            $query->where('categories.type', 'expense');
        }

        if (!empty($this->selectedCategories)) {
            $query->whereIn('category_id', $this->selectedCategories);
        }

        $this->categoryData = $query
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(try_equivalent) as total_amount'),
                DB::raw('AVG(try_equivalent) as average_amount')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * Calculates the metrics (total amount, average amount, most frequently used categories)
     */
    private function calculateMetrics()
    {
        $this->totalAmount = $this->categoryData->sum('total_amount');
        $this->averageAmount = $this->categoryData->avg('average_amount');
        
        // Most frequently used categories
        $this->topCategories = $this->categoryData
            ->sortByDesc('total_amount')
            ->take(5)
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_amount' => $category->total_amount,
                    'transaction_count' => $category->transaction_count,
                    'average_amount' => $category->average_amount,
                    'percentage' => ($category->total_amount / $this->totalAmount) * 100
                ];
            });
    }

    /**
     * Analyzes the category trends
     */
    private function analyzeTrends()
    {
        // Analyzes the category growth trends
        foreach ($this->categoryData as $category) {
            $previousPeriodData = Transaction::query()
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->where('category_id', $category->id)
                ->whereBetween('transactions.date', [
                    Carbon::parse($this->startDate)->subMonths(3),
                    Carbon::parse($this->startDate)->subDay()
                ])
                ->select(DB::raw('SUM(try_equivalent) as total_amount'))
                ->first();

            $previousAmount = $previousPeriodData->total_amount ?? 0;
            $currentAmount = $category->total_amount;

            $growth = $previousAmount > 0 
                ? (($currentAmount - $previousAmount) / $previousAmount) * 100 
                : ($currentAmount > 0 ? 100 : 0);

            $this->categoryGrowth[$category->id] = [
                'percentage' => round($growth, 2),
                'trend' => $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'stable')
            ];
        }
    }

    /**
     * When the start date is updated, reloads the data
     */
    public function updatedStartDate()
    {
        $this->loadData();
    }

    /**
     * When the end date is updated, reloads the data
     */
    public function updatedEndDate()
    {
        $this->loadData();
    }

    /**
     * When the selected categories are updated, reloads the data
     */
    public function updatedSelectedCategories()
    {
        $this->loadData();
    }

    /**
     * When the analysis type is updated, resets the selected categories and reloads the data
     */
    public function updatedAnalysisType()
    {
        $this->selectedCategories = [];
        $this->loadData();
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.analysis.category-analysis', [
            'categories' => Category::where('type', $this->analysisType)
                ->orderBy('name')
                ->get()
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
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('startDate')
                        ->label('تاريخ البداية')
                        ->displayFormat('d.m.Y')
                        ->native(false)
                        ->required()
                        ->live(),
                    Forms\Components\DatePicker::make('endDate')
                        ->label('تاريخ النهاية')
                        ->displayFormat('d.m.Y')
                        ->native(false)
                        ->required()
                        ->live(),
                ]),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('analysisType')
                        ->label('نوع التحليل')
                        ->options([
                            'income' => 'فئات الايراد',
                            'expense' => 'فئات المصروفات'
                        ])
                        ->required()
                        ->live()
                        ->native(false),
                    Forms\Components\Select::make('selectedCategories')
                        ->label('الأنواع')
                        ->multiple()
                        ->native(false)
                        ->options(function () {
                            return Category::where('type', $this->analysisType)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->placeholder('كل الفئات')
                        ->live()
                ]),
        ];
    }
} 