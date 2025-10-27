<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer Agreement Model
 *
 * This model manages agreements and payments made with customers.
 * Features:
 * - Agreement details (name, description, amount)
 * - Payment tracking (start date, next payment date)
 * - Agreement status (active, completed, cancelled)
 * - Notes and history
 *
 * @package App\Models
 */
class CustomerAgreement extends Model
{
    use SoftDeletes, HasFactory;

    /** @var array Fillable attributes */
    protected $fillable = [
        'user_id',
        'customer_id',
        'name',
        'description',
        'amount',
        'start_date',
        'next_payment_date',
        'status',
        'notes',
    ];

    /** @var array Attributes to be cast as JSON */
    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'status' => 'string',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get status options for forms.
     *
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return self::STATUSES;
    }

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    /**
     * Customer relationship
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * User relationship
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 