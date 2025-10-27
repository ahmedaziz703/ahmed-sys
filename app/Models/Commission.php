<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Commission model
 *
 * Represents commission records for income transactions.
 * Each commission belongs to a user and is associated with a specific transaction.
 * Tracks commission rates and amounts earned by users.
 */
class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'commission_rate',
        'commission_amount'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2'
    ];

    /**
     * Get commission statistics for a given user.
     *
     * @param int $userId
     * @param string|null $period Optional period filter: "this_month" or "last_month"
     * @return array<string, int|float>
     */
    public static function getUserStats(int $userId, ?string $period = null): array
    {
        $query = self::where('user_id', $userId);

        // Period filter
        if ($period === 'this_month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        } elseif ($period === 'last_month') {
            $query->whereMonth('created_at', now()->subMonth()->month)
                  ->whereYear('created_at', now()->subMonth()->year);
        }

        // Totals
        $totalStats = $query->select([
            DB::raw('SUM(commission_amount) as total_commission'),
            DB::raw('COUNT(*) as total_transactions')
        ])->first();

        // Payouts
        $payoutStats = CommissionPayout::where('user_id', $userId)
            ->when($period === 'this_month', function ($q) {
                $q->whereMonth('payment_date', now()->month)
                  ->whereYear('payment_date', now()->year);
            })
            ->when($period === 'last_month', function ($q) {
                $q->whereMonth('payment_date', now()->subMonth()->month)
                  ->whereYear('payment_date', now()->subMonth()->year);
            })
            ->select(DB::raw('SUM(amount) as total_paid'))
            ->first();

        $totalCommission = $totalStats->total_commission ?? 0;
        $totalPaid = $payoutStats->total_paid ?? 0;

        return [
            'total_commission' => $totalCommission,
            'total_paid' => $totalPaid,
            'total_pending' => max(0, $totalCommission - $totalPaid),
            'total_transactions' => $totalStats->total_transactions ?? 0
        ];
    }

    /**
     * The user who earned the commission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The transaction associated with the commission.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
} 