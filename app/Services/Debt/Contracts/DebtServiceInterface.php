<?php

declare(strict_types=1);

namespace App\Services\Debt\Contracts;

use App\Models\Debt;
use App\DTOs\Debt\DebtData;

/**
 * Debt service interface
 * 
 * Defines the methods required for managing debt operations.
 * Handles debt creation, updating, deletion, and status updates.
 */
interface DebtServiceInterface
{
    /**
     * Create a new debt/credit record.
     * 
     * @param DebtData $data Debt/credit data
     * @return Debt Created debt/credit record
     */
    public function create(DebtData $data): Debt;

    /**
     * Update an existing debt/credit record.
     * 
     * @param Debt $debt Debt/credit record to update
     * @param DebtData $data New debt/credit data
     * @return Debt Updated debt/credit record
     */
    public function update(Debt $debt, DebtData $data): Debt;

    /**
     * Delete a debt/credit record.
     * 
     * @param Debt $debt Debt/credit record to delete
     */
    public function delete(Debt $debt): void;

    /**
     * Update the status of a debt/credit record.
     * 
     * @param Debt $debt Debt/credit record to update
     */
    public function updateStatus(Debt $debt): void;

    /**
     * Get sorted debt/credit records.
     * 
     * @param string $sortBy Sorting field
     * @param string $direction Sorting direction
     * @return \Illuminate\Database\Eloquent\Collection Sorted debt/credit records
     */
    public function getSortedDebts(string $sortBy = 'due_date', string $direction = 'asc'): \Illuminate\Database\Eloquent\Collection;

    /*
     * Add a payment to a debt/credit record.
     * 
     * @param Debt $debt Debt/credit record to add payment to
     * @param array $data Payment data
     */
    // public function addPayment(Debt $debt, array $data): void;
}