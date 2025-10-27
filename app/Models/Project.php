<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Project model
 * 
 * Represents projects used for project management.
 * Each project is created by a user and can contain one or more boards.
 */
class Project extends Model
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
        'created_by',
        'view_type',
    ];

    /**
     * Methods to run when the model is booted.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($project) {
            $project->board()->create(['name' => 'Main Board']);
        });
    }

    /**
     * The user who created the project.
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Boards belonging to the project.
     * 
     * @return HasMany
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /**
     * The project's main board.
     * 
     * @return HasOne
     */
    public function board(): HasOne
    {
        return $this->hasOne(Board::class);
    }
}