<?php

namespace App\Models;

/**
 * Note interface
 *
 * Defines the basic methods that note models must implement.
 * Each note must belong to a customer and a user.
 */
interface Note
{
    /**
     * The customer that the note belongs to.
     *
     * @return BelongsTo
     */
    public function customer();

    /**
     * The user who created the note.
     *
     * @return BelongsTo
     */
    public function user();
}