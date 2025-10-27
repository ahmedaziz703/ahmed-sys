<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer Sensitive Information model
 * 
 * Manages customers' sensitive information (domain, hosting, server, etc.).
 * Features:
 * - Store sensitive information encrypted
 * - Add/edit/delete information
 * - Track information history
 * - User-based authorization
 * 
 * @package App\Models
 */
class CustomerCredential extends Model
{
    use SoftDeletes, HasFactory;

    /** @var array Fillable attributes */
    protected $fillable = [
        'user_id',
        'customer_id',
        'name',
        'value',
        'status',
    ];

    /** @var array Attributes stored as JSON */
    protected $casts = [
        'status' => 'boolean',
    ];

    /** @var array Attributes to encrypt */
    protected $encryptable = [
        'value',
    ];

    /**
     * Customer relationship
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * User relationship
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Before encrypting values
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            foreach ($model->encryptable as $field) {
                if (isset($model->attributes[$field])) {
                    // Convert array to JSON first, then encrypt
                    $model->attributes[$field] = encrypt(json_encode($model->attributes[$field]));
                }
            }
        });

        static::retrieved(function ($model) {
            foreach ($model->encryptable as $field) {
                if (isset($model->attributes[$field])) {
                    try {
                        // Decrypt and convert JSON back to array
                        $decrypted = decrypt($model->attributes[$field]);
                        $model->attributes[$field] = json_decode($decrypted, true) ?: [];
                    } catch (\Exception $e) {
                        $model->attributes[$field] = [];
                    }
                }
            }
        });
    }
} 