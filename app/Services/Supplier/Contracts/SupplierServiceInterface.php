<?php

declare(strict_types=1);

namespace App\Services\Supplier\Contracts;

use App\Models\Supplier;
use App\DTOs\Supplier\SupplierData;

/**
 * Supplier service interface
 * 
 * Defines the methods required for managing supplier operations.
 * Handles supplier creation, updating, and deletion.
 */
interface SupplierServiceInterface
{
    /**
     * Create a new supplier.
     * 
     * @param SupplierData $data Supplier data
     * @return Supplier Created supplier
     */
    public function create(SupplierData $data): Supplier;

    /**
     * Update an existing supplier.
     * 
     * @param Supplier $supplier Supplier to update
     * @param SupplierData $data New supplier data
     * @return Supplier Updated supplier
     */
    public function update(Supplier $supplier, SupplierData $data): Supplier;

    /**
     * Delete a supplier.
     * 
     * @param Supplier $supplier Supplier to delete
     */
    public function delete(Supplier $supplier): void;
}