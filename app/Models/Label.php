<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Label model
 * 
 * Represents labels used to categorize and group tasks.
 * Each label is associated with a color and can be assigned to multiple tasks.
 */
class Label extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'color',
    ];

    /**
     * Tasks that the label is assigned to.
     *
     * @return BelongsToMany
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_labels');
    }
}