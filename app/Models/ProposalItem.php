<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Proposal Item model
 * 
 * Represents product or service line items within proposals.
 * Each item belongs to a proposal and contains price, quantity, tax, and discount details.
 */
class ProposalItem extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'proposal_id',
        'name',
        'description',
        'price',
        'quantity',
        'unit',
        'discount',
        'tax_rate',
        'tax_included',
        'total',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'discount' => 'float',
        'tax_rate' => 'float',
        'tax_included' => 'boolean',
        'total' => 'float',
    ];

    /**
     * The proposal the item belongs to.
     *
     * @return BelongsTo
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}