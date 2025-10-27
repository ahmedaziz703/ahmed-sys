<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Task-Label Relationship model
 *
 * Represents the many-to-many relationship between tasks and labels.
 * Used as a pivot table.
 */
class TaskLabel extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     *
     * @var array<string>
     */
    protected $fillable = [
        'task_id',
        'label_id',
    ];

    /**
     * Related task
     *
     * @return BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Related label
     *
     * @return BelongsTo
     */
    public function label()
    {
        return $this->belongsTo(Label::class);
    }
}