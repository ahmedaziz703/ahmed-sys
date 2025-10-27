<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Task List model
 * 
 * Represents task lists on project boards.
 * Each list belongs to a board and contains sortable tasks.
 */
class TaskList extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'board_id',
        'name',
        'order',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Sorting configuration
     *
     * @var array<string, mixed>
     */
    public $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => true,
    ];

    /**
     * The board that the list belongs to.
     * 
     * @return BelongsTo
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Tasks belonging to the list.
     * 
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }
}