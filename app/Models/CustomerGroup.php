<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer Group model
 * 
 * Enables grouping of customers.
 * Each group belongs to a user and can include multiple customers.
 */
class CustomerGroup extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
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
     * Customers belonging to the group.
     * 
     * @return HasMany
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * The user who owns the group.
     * 
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}