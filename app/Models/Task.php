<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Task model
 * 
 * Represents tasks used in project management.
 * Each task belongs to a task list and can include priority and due date details.
 * Tasks are sortable and can be labeled.
 */
class Task extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'content',
        'checklist',
        'priority',
        'due_date',
        'order',
        'assigned_to',
        'task_list_id'
    ];

    /**
     * Attribute casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
        'checklist' => 'array',
        'due_date' => 'datetime',
        'completed' => 'boolean',
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
     * The task list to which the task belongs.
     * 
     * @return BelongsTo
     */
    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    /**
     * The user assigned to the task.
     * 
     * @return BelongsTo
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Labels assigned to the task.
     * 
     * @return BelongsToMany
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_labels');
    }

    /**
     * Task validation rules.
     * 
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}