<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Board model
 * 
 * Represents boards used in project management.
 * Each board belongs to a project and can contain multiple task lists.
 */
class Board extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'project_id',
        'name',
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'project_id' => 'integer',
    ];

    /**
     * The project that the board belongs to.
     * 
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Task lists belonging to the board.
     * 
     * @return HasMany
     */
    public function taskLists(): HasMany
    {
        return $this->hasMany(TaskList::class)->orderBy('order');
    }
}