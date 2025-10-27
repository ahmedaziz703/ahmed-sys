<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Proposal model
 *
 * Represents proposals submitted to customers.
 * Each proposal belongs to a customer and can contain multiple proposal items.
 * Tracks validity period, payment terms, and total amounts of the proposal.
 */
class Proposal extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'number',
        'title',
        'content',
        'valid_until',
        'status',
        'payment_terms',
        'notes',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'currency',
        'created_by',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valid_until' => 'date',
        'subtotal' => 'float',
        'tax_total' => 'float',
        'discount_total' => 'float',
        'total' => 'float',
    ];

    /**
     * The customer that the proposal belongs to
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The user who created the proposal
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Proposal items belonging to the proposal
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }
}