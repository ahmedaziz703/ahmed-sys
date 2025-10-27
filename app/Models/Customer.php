<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer model
 * 
 * Represents the business's customers.
 * Each customer may belong to a user and a customer group.
 * Income transactions and notes related to customers can be tracked.
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'tax_number',
        'tax_office',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'description',
        'status',
        'customer_group_id',
        'user_id',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * Model boot method - prevent changing user_id.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($customer) {
            // If user_id is attempted to be changed, keep the original value
            if ($customer->isDirty('user_id')) {
                $customer->user_id = $customer->getOriginal('user_id');
            }
        });
    }

    /**
     * The user who owns the customer.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The group the customer belongs to.
     * 
     * @return BelongsTo
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * Income transactions related to the customer.
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id')
            ->where('type', 'income')
            ->latest('date');
    }

    /**
     * Notes related to the customer.
     * 
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    /**
     * Credentials related to the customer.
     *
     * @return HasMany
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(CustomerCredential::class);
    }

    /**
     * Agreements related to the customer.
     *
     * @return HasMany
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(CustomerAgreement::class);
    }
}