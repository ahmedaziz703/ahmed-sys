<?php

namespace App\Services\Commission;

use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Transaction;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Create a commission for an income transaction.
     */
    public function createCommissionForTransaction(Transaction $transaction): ?Commission
    {
        // Only calculate commission for income transactions
        if ($transaction->type !== TransactionTypeEnum::INCOME->value) {
            return null;
        }

        // Find the customer or lead
        $customer = Customer::find($transaction->customer_id);
        $lead = Lead::find($transaction->lead_id);
        
        // Find the user who created the transaction
        $user = $customer ? $customer->user : ($lead ? $lead->user : null);
        
        // If the user doesn't exist or doesn't have commission, don't process
        if (!$user || !$user->has_commission) {
            return null;
        }

        // Calculate the commission amount
        $commissionAmount = ($transaction->try_equivalent * $user->commission_rate) / 100;

        // Create the commission record
        return Commission::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'commission_rate' => $user->commission_rate,
            'commission_amount' => $commissionAmount
        ]);
    }

    /**
     * Create a commission payout.
     */
    public function createPayout(
        int $userId,
        float $amount,
        string $paymentDate,
        string $periodStart,
        string $periodEnd,
        ?string $notes = null
    ): CommissionPayout {
        return CommissionPayout::create([
            'user_id' => $userId,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'notes' => $notes
        ]);
    }

    /**
     * Get the commission statistics for a specific user.
     */
    public function getUserStats(int $userId, ?string $period = null): array
    {
        $commissionQuery = Commission::where('user_id', $userId);
        $payoutQuery = CommissionPayout::where('user_id', $userId);

        // Period filter
        if ($period === 'this_month') {
            $commissionQuery->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
            
            $payoutQuery->whereMonth('payment_date', now()->month)
                       ->whereYear('payment_date', now()->year);
        } elseif ($period === 'last_month') {
            $commissionQuery->whereMonth('created_at', now()->subMonth()->month)
                          ->whereYear('created_at', now()->subMonth()->year);
            
            $payoutQuery->whereMonth('payment_date', now()->subMonth()->month)
                       ->whereYear('payment_date', now()->subMonth()->year);
        }

        // Total commission
        $totalStats = $commissionQuery->select([
            DB::raw('SUM(commission_amount) as total_commission'),
            DB::raw('COUNT(*) as total_transactions')
        ])->first();

        // Payments
        $payoutStats = $payoutQuery->select(DB::raw('SUM(amount) as total_paid'))->first();

        $totalCommission = $totalStats->total_commission ?? 0;
        $totalPaid = $payoutStats->total_paid ?? 0;

        return [
            'total_commission' => $totalCommission,
            'total_paid' => $totalPaid,
            'total_pending' => max(0, $totalCommission - $totalPaid),
            'total_transactions' => $totalStats->total_transactions ?? 0
        ];
    }
} 