<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Budget model
 * 
 * Represents users' budget plans.
 * Each budget belongs to a category and is planned for a specific period.
 */
class Budget extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'amount',
        'start_date',
        'end_date',
        'period',
        'status'
    ];
    
    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'status' => 'boolean'
    ];
    
    /**
     * The user who owns the budget.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * The category the budget belongs to.
     * 
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
