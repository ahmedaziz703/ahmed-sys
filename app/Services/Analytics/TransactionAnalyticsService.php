<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Transaction analytics service
 * 
 * Contains methods for analyzing and reporting transactions.
 * Provides income-expense summaries, cash flow, spending by category, and other analyses.
 */
class TransactionAnalyticsService
{
    /**
     * Get the income-expense summary for a specific period.
     * 
     * @param string $period Period type (day, week, month, quarter, year)
     * @param Carbon|null $date Reference date
     * @return array Income-expense summary and change percentages
     */
    public function getSummary(string $period = 'month', ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        // Current period data
        $currentPeriodData = $this->getTransactionTotals($startDate, $endDate);
        
        // Previous period for comparison
        $previousStartDate = $this->getPreviousPeriodStartDate($date, $period);
        $previousEndDate = $this->getPreviousPeriodEndDate($date, $period);
        $previousPeriodData = $this->getTransactionTotals($previousStartDate, $previousEndDate);
        
        // Calculate percentages
        $incomeChange = $this->calculatePercentageChange(
            $previousPeriodData['income'] ?? 0, 
            $currentPeriodData['income'] ?? 0
        );
        
        $expenseChange = $this->calculatePercentageChange(
            $previousPeriodData['expense'] ?? 0, 
            $currentPeriodData['expense'] ?? 0
        );
        
        $balanceChange = $this->calculatePercentageChange(
            ($previousPeriodData['income'] ?? 0) - ($previousPeriodData['expense'] ?? 0),
            ($currentPeriodData['income'] ?? 0) - ($currentPeriodData['expense'] ?? 0)
        );
        
        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'income' => [
                'amount' => $currentPeriodData['income'] ?? 0,
                'change_percentage' => $incomeChange,
                'previous_amount' => $previousPeriodData['income'] ?? 0,
            ],
            'expense' => [
                'amount' => $currentPeriodData['expense'] ?? 0,
                'change_percentage' => $expenseChange,
                'previous_amount' => $previousPeriodData['expense'] ?? 0,
            ],
            'balance' => [
                'amount' => ($currentPeriodData['income'] ?? 0) - ($currentPeriodData['expense'] ?? 0),
                'change_percentage' => $balanceChange,
                'previous_amount' => ($previousPeriodData['income'] ?? 0) - ($previousPeriodData['expense'] ?? 0),
            ],
        ];
    }
    
    /**
     * Get the monthly balance information for a specific month.
     * 
     * @param int $year Year
     * @param int $month Month
     * @param int|null $userId User ID
     * @return array Monthly income, expenses and transfer information
     */
    public function getMonthlyBalance(int $year, int $month, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        
        $income = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $expenses = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $transfers = Transaction::where('user_id', $userId)
            ->where('type', 'transfer')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        return [
            'income' => $income,
            'expenses' => $expenses,
            'transfers' => $transfers,
            'net' => $income - $expenses,
            'period' => $startDate->format('F Y')
        ];
    }
    
    /**
     * Get the cash flow data for a specific period.
     * 
     * @param int|null $userId User ID
     * @param Carbon|null $startDate Start date
     * @param Carbon|null $endDate End date
     * @return array Monthly cash flow data
     */
    public function getCashFlow(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $userId = $userId ?? auth()->id();
        $startDate = $startDate ?? Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();
        
        $transactions = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($transaction) {
                return Carbon::parse($transaction->date)->format('Y-m');
            });
            
        $cashFlow = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $yearMonth = $currentDate->format('Y-m');
            
            $monthlyTransactions = $transactions[$yearMonth] ?? collect();
            
            $income = $monthlyTransactions->where('type', 'income')->sum('amount');
            $expenses = $monthlyTransactions->where('type', 'expense')->sum('amount');
            
            $cashFlow[] = [
                'period' => $currentDate->format('M Y'),
                'income' => $income,
                'expenses' => $expenses,
                'net' => $income - $expenses
            ];
            
            $currentDate->addMonth();
        }
        
        return $cashFlow;
    }
    
    /**
     * Get the transaction totals for a specific date range.
     * 
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Transaction totals by type
     */
    private function getTransactionTotals(Carbon $startDate, Carbon $endDate): array
    {
        $results = Transaction::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type')
            ->toArray();
            
        return $results;
    }
    
    /**
     * Calculate the percentage change between two values.
     * 
     * @param float $oldValue Old value
     * @param float $newValue New value
     * @return float Percentage change
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }
    
    /**
     * Calculate the start date for a specific period.
     * 
     * @param Carbon $date Reference date
     * @param string $period Period type
     * @return Carbon Start date
     */
    private function getPeriodStartDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->startOfDay();
            case 'week':
                return $date->copy()->startOfWeek();
            case 'month':
                return $date->copy()->startOfMonth();
            case 'quarter':
                return $date->copy()->startOfQuarter();
            case 'year':
                return $date->copy()->startOfYear();
            default:
                return $date->copy()->startOfMonth();
        }
    }
    
    /**
     * Calculate the end date for a specific period.
     * 
     * @param Carbon $date Reference date
     * @param string $period Period type
     * @return Carbon End date
     */
    private function getPeriodEndDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->endOfDay();
            case 'week':
                return $date->copy()->endOfWeek();
            case 'month':
                return $date->copy()->endOfMonth();
            case 'quarter':
                return $date->copy()->endOfQuarter();
            case 'year':
                return $date->copy()->endOfYear();
            default:
                return $date->copy()->endOfMonth();
        }
    }
    
    /**
     * Calculate the start date for the previous period.
     * 
     * @param Carbon $date Reference date
     * @param string $period Period type
     * @return Carbon Previous period start date
     */
    private function getPreviousPeriodStartDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->subDay()->startOfDay();
            case 'week':
                return $date->copy()->subWeek()->startOfWeek();
            case 'month':
                return $date->copy()->subMonth()->startOfMonth();
            case 'quarter':
                return $date->copy()->subQuarter()->startOfQuarter();
            case 'year':
                return $date->copy()->subYear()->startOfYear();
            default:
                return $date->copy()->subMonth()->startOfMonth();
        }
    }
    
    /**
     * Calculate the end date for the previous period.
     * 
     * @param Carbon $date Reference date
     * @param string $period Period type
     * @return Carbon Previous period end date
     */
    private function getPreviousPeriodEndDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->subDay()->endOfDay();
            case 'week':
                return $date->copy()->subWeek()->endOfWeek();
            case 'month':
                return $date->copy()->subMonth()->endOfMonth();
            case 'quarter':
                return $date->copy()->subQuarter()->endOfQuarter();
            case 'year':
                return $date->copy()->subYear()->endOfYear();
            default:
                return $date->copy()->subMonth()->endOfMonth();
        }
    }
    
    /**
     * Get the spending by category for a specific period.
     * 
     * @param string $period Period type
     * @param Carbon|null $date Reference date
     * @return Collection Spending by category data
     */
    public function getSpendingByCategory(string $period = 'month', ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name ?? 'Uncategorized',
                    'amount' => $item->total,
                ];
            });
    }
    
    /**
     * Get the income by category for a specific period.
     * 
     * @param string $period Period type
     * @param Carbon|null $date Reference date
     * @return Collection Income by category data
     */
    public function getIncomeByCategory(string $period = 'month', ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return Transaction::where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name ?? 'Uncategorized',
                    'amount' => $item->total,
                ];
            });
    }
    
    /**
     * Get the spending by tags for a specific period.
     * 
     * @param array $tags Tags
     * @param string $period Period type
     * @param Carbon|null $date Reference date
     * @return Collection Spending by tags data
     */
    public function getSpendingByTags(array $tags, string $period = 'month', ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereJsonContains('tags', $tags)
            ->selectRaw('JSON_EXTRACT(tags, "$[0]") as tag, SUM(amount) as total')
            ->groupBy('tag')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'tag' => json_decode($item->tag),
                    'amount' => $item->total,
                ];
            });
    }
    
    /**
     * Get the monthly trends for a specific year.
     * 
     * @param int|null $year Year
     * @return array Monthly income-expense trends
     */
    public function getMonthlyTrends(int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfMonth();
        
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $currentStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $currentEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            $totals = $this->getTransactionTotals($currentStart, $currentEnd);
            
            $monthlyData[] = [
                'month' => $currentStart->format('M'),
                'income' => $totals['income'] ?? 0,
                'expense' => $totals['expense'] ?? 0,
                'balance' => ($totals['income'] ?? 0) - ($totals['expense'] ?? 0),
            ];
        }
        
        return $monthlyData;
    }
    
    /**
     * Get the account activity for all accounts.
     * 
     * @param string $period Period type
     * @return Collection Account activity data
     */
    public function getAccountActivity(string $period = 'month'): Collection
    {
        $date = Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return BankAccount::with(['transactions' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function ($account) {
            // Calculate totals
            $income = $account->transactions->where('type', 'income')->sum('amount');
            $expense = $account->transactions->where('type', 'expense')->sum('amount');
            
            return [
                'account_id' => $account->id,
                'account_name' => $account->bank_name,
                'current_balance' => $account->balance,
                'period_income' => $income,
                'period_expense' => $expense,
                'period_change' => $income - $expense,
                'transaction_count' => $account->transactions->count(),
            ];
        });
    }
    
    /**
     * Get the balance history for a specific account.
     * 
     * @param int $accountId Account ID
     * @param int $months Month count
     * @return array Daily balance history
     */
    public function getAccountBalanceHistory(int $accountId, int $months = 6): array
    {
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subMonths($months)->startOfDay();
        
        $account = BankAccount::findOrFail($accountId);
        
        // Get all transactions affecting this account (income, expense, transfer)
        $incomeTransactions = Transaction::join('income_transactions', 'transactions.id', '=', 'income_transactions.transaction_id')
            ->where('income_transactions.bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $expenseTransactions = Transaction::join('expense_transactions', 'transactions.id', '=', 'expense_transactions.transaction_id')
            ->where('expense_transactions.bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $transferInTransactions = Transaction::join('transfer_transactions', 'transactions.id', '=', 'transfer_transactions.transaction_id')
            ->where('transfer_transactions.target_bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $transferOutTransactions = Transaction::join('transfer_transactions', 'transactions.id', '=', 'transfer_transactions.transaction_id')
            ->where('transfer_transactions.source_bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $allTransactions = $incomeTransactions->concat($expenseTransactions)
            ->concat($transferInTransactions)
            ->concat($transferOutTransactions)
            ->sortBy('date');
            
        $history = [];
        $runningBalance = $account->initial_balance ?? 0;
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayTransactions = $allTransactions->filter(function ($transaction) use ($currentDate) {
                return Carbon::parse($transaction->date)->format('Y-m-d') === $currentDate->format('Y-m-d');
            });
            
            foreach ($dayTransactions as $transaction) {
                if ($transaction->type === 'income') {
                    $runningBalance += $transaction->amount;
                } elseif ($transaction->type === 'expense') {
                    $runningBalance -= $transaction->amount;
                } elseif ($transaction->type === 'transfer') {
                    if ($transaction->transferTransaction->source_bank_account_id === $accountId) {
                        $runningBalance -= $transaction->amount;
                    } else {
                        $runningBalance += $transaction->amount;
                    }
                }
            }
            
            $history[] = [
                'date' => $dateKey,
                'balance' => $runningBalance
            ];
            
            $currentDate->addDay();
        }
        
        return $history;
    }
}