<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayout extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'payment_date',
        'period_start',
        'period_end',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date'
    ];

    /**
     * The user who received the commission payout.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
