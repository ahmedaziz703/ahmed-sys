<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lead model
 * 
 * Represents the business's potential customers.
 * Each lead can be assigned to a user and converted into a customer.
 * Tracks lead status, contact info, and conversion process.
 */
class Lead extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'source',
        'status',
        'last_contact_date',
        'next_contact_date',
        'notes',
        'assigned_to',
        'converted_at',
        'converted_to_customer_id',
        'conversion_reason',
        'user_id',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_contact_date' => 'datetime',
        'next_contact_date' => 'datetime',
        'converted_at' => 'datetime',
        'status' => 'string',
    ];

    /**
     * Model boot method - prevent changing user_id.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($lead) {
            // If user_id is attempted to be changed, keep the original value
            if ($lead->isDirty('user_id')) {
                $lead->user_id = $lead->getOriginal('user_id');
            }
        });
    }

    /**
     * The user who owns the lead.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user assigned to the lead.
     * 
     * @return BelongsTo
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * The customer the lead was converted into.
     * 
     * @return BelongsTo
     */
    public function convertedToCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }
}