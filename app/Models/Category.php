<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Category model
 * 
 * Represents categories for transactions.
 * Each category belongs to a user and can have a specific type.
 */
class Category extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'color',
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
     * The user who owns the category.
     * 
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}