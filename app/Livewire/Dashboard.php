<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Contracts\View\View;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Lead;
use App\Models\CustomerNote;
use App\Models\Task;


final class Dashboard extends Component
{
    public array $stats = [];
    public array $chartData = [];
    public array $customerGrowthData = [];
    public array $months = [];
    public array $assignedCustomers = [];
    public array $customerNotes = [];
    public array $commissionData = [];

    protected $listeners = ['refreshCharts' => 'refreshData'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        if (auth()->user()->hasRole('admin')) {
            $this->loadAdminDashboard();
        } else {
            $this->loadEmployeeDashboard();
        }
    }

    public function refreshData(): void
    {
        $this->loadData();
    }

    private function loadAdminDashboard(): void
    {
        // Customer and lead statistics
        $this->stats = [
            'total_customers' => Lead::count(),
            'potential_customers' => Lead::where('status', '!=', 'lost')->count(),
            'negotiating_customers' => Lead::where('status', 'negotiating')->count(),

            // Commission stats for this month
            'total_commission' => Commission::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission_amount'),

            'total_commission_paid' => CommissionPayout::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),

            'pending_commission' => Commission::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission_amount') - 
                CommissionPayout::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),

            'active_projects' => Project::where('status', 'active')->count(),
            'total_accounts' => Account::count(),
        ];

        // Fetch income/expense data for the last 6 months
        $this->chartData = $this->getLastSixMonthsData();
        
        // Fetch customer growth data
        $this->customerGrowthData = $this->getCustomerGrowthData();
    }

    private function loadEmployeeDashboard(): void
    {
        $user = auth()->user();
        $now = now();
        
        // Basic statistics
        $this->stats = [
            // Pending activities (scheduled in the future)
            'pending_activities' => CustomerNote::where('assigned_user_id', $user->id)
                ->whereIn('type', ['call', 'meeting', 'email', 'other'])
                ->where('activity_date', '>', $now)
                ->count(),

            // Commission earned this month
            'earned_commission' => Commission::where('user_id', $user->id)
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->sum('commission_amount'),

            // Commission paid this month
            'paid_commission' => CommissionPayout::where('user_id', $user->id)
                ->whereYear('payment_date', $now->year)
                ->whereMonth('payment_date', $now->month)
                ->sum('amount'),

            // Lifetime total commission (paid + unpaid)
            'total_commission' => Commission::where('user_id', $user->id)
                ->sum('commission_amount')
        ];

        // Upcoming notes
        $this->customerNotes = CustomerNote::with(['customer', 'user', 'assignedUser'])
            ->where('assigned_user_id', $user->id)
            ->orderBy('activity_date', 'asc')
            ->where('activity_date', '>=', now())
            ->take(10)
            ->get()
            ->map(function ($note) {
                $activityDate = Carbon::parse($note->activity_date);
                $isToday = $activityDate->isToday();
                $isTomorrow = $activityDate->isTomorrow();
                $isThisWeek = $activityDate->isCurrentWeek();
                
                $formattedDate = match(true) {
                    $isToday => 'اليوم ' . $activityDate->format('H:i'),
                    $isTomorrow => 'غداً ' . $activityDate->format('H:i'),
                    $isThisWeek => $activityDate->locale('ar')->dayName . ' ' . $activityDate->format('H:i'),
                    default => $activityDate->locale('ar')->translatedFormat('d F Y H:i')
                };

                return [
                    'id' => $note->id,
                    'type' => $note->type,
                    'content' => $note->content,
                    'created_at' => $note->created_at,
                    'activity_date' => $note->activity_date,
                    'formatted_date' => $formattedDate,
                    'is_upcoming' => $activityDate->isFuture(),
                    'customer' => [
                        'id' => $note->customer->id,
                        'name' => $note->customer->name
                    ],
                    'user' => [
                        'name' => $note->user->name
                    ],
                    'assigned_user' => [
                        'name' => $note->assignedUser->name
                    ]
                ];
            })
            ->toArray();

        // Commission data (if the user is commission-enabled)
        if ($user->has_commission) {
            $this->commissionData = $this->getCommissionData($user->id);
        }
    }

    private function getCommissionData($userId): array
    {
        $months = collect([]);
        $arabicMonths = [
            'Jan' => 'يناير', 'Feb' => 'فبراير', 'Mar' => 'مارس', 'Apr' => 'أبريل',
            'May' => 'مايو', 'Jun' => 'يونيو', 'Jul' => 'يوليو', 'Aug' => 'أغسطس',
            'Sep' => 'سبتمبر', 'Oct' => 'أكتوبر', 'Nov' => 'نوفمبر', 'Dec' => 'ديسمبر'
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('M');
            
            $earned = Commission::where('user_id', $userId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('commission_amount');

            $paid = CommissionPayout::where('user_id', $userId)
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');

            $months->push([
                'month' => $arabicMonths[$monthKey],
                'earned' => $earned,
                'paid' => $paid
            ]);
        }

        return $months->toArray();
    }

    private function getLastSixMonthsData(): array
    {
        $months = collect([]);
        $arabicMonths = [
            'Jan' => 'يناير', 'Feb' => 'فبراير', 'Mar' => 'مارس', 'Apr' => 'أبريل',
            'May' => 'مايو', 'Jun' => 'يونيو', 'Jul' => 'يوليو', 'Aug' => 'أغسطس',
            'Sep' => 'سبتمبر', 'Oct' => 'أكتوبر', 'Nov' => 'نوفمبر', 'Dec' => 'ديسمبر'
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('M');
            
            // Get income and expense values as float
            $income = (float) Transaction::where('type', 'income')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount') ?: 0;
                
            $expense = (float) Transaction::where('type', 'expense')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount') ?: 0;

            $months->push([
                'month' => $arabicMonths[$monthKey],
                'income' => $income,
                'expense' => $expense
            ]);
        }

        return $months->toArray();
    }

    private function getCustomerGrowthData(): array
    {
        $months = collect([]);
        $arabicMonths = [
            'Jan' => 'يناير', 'Feb' => 'فبراير', 'Mar' => 'مارس', 'Apr' => 'أبريل',
            'May' => 'مايو', 'Jun' => 'يونيو', 'Jul' => 'يوليو', 'Aug' => 'أغسطس',
            'Sep' => 'سبتمبر', 'Oct' => 'أكتوبر', 'Nov' => 'نوفمبر', 'Dec' => 'ديسمبر'
        ];

        // Get the last 6 months of data
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('M');
            
            // Number of leads created in that month
            $total = Lead::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $months->push([
                'month' => $arabicMonths[$monthKey],
                'total' => $total
            ]);
        }

        return $months->toArray();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
