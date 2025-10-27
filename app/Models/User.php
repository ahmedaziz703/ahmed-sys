<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model
 * 
 * Represents users in the system.
 * Provides authentication and authorization features for each user.
 * Supports role-based authorization via the Spatie/Permission package.
 * Includes soft delete capability and commission management support.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'has_commission',
        'commission_rate',
    ];

    /**
     * Hidden attributes
     * 
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'has_commission' => 'boolean',
        'commission_rate' => 'decimal:2',
        'password' => 'hashed',
    ];

    /**
     * Determine whether the user is eligible for commission.
     *
     * @return bool
     */
    public function isEligibleForCommission(): bool
    {
        return $this->has_commission && $this->commission_rate > 0;
    }

    /**
     * Get the AI conversations for the user.
     *
     * @return HasMany
     */
    public function aiConversations()
    {
        return $this->hasMany(AIConversation::class);
    }

    /**
     * Get the transactions for the user.
     *
     * @return HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

}