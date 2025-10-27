<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Setting model
 * 
 * Represents application-wide settings.
 * Settings are stored by group and can contain different value types.
 * Each setting is stored as a key-value pair.
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    /**
     * Attribute casts
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
    ];
}