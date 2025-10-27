<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Supplier model
 * 
 * Represents the business's suppliers.
 * Keeps contact information and debt records for each supplier.
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'notes',
        'status',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Debts related to the supplier.
     * 
     * @return HasMany
     */
    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class, 'supplier_id');
    }
}