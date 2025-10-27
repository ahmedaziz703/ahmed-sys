<?php

declare(strict_types=1);

namespace App\Services\Supplier\Implementations;

use App\Models\Supplier;
use App\Services\Supplier\Contracts\SupplierServiceInterface;
use App\DTOs\Supplier\SupplierData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Supplier service implementation
 * 
 * Contains methods required to manage supplier operations.
 * Handles supplier creation, updating, and deletion.
 */
final class SupplierService implements SupplierServiceInterface
{
    /**
     * Create a new supplier.
     * 
     * @param SupplierData $data Supplier data
     * @return Supplier Created supplier
     */
    public function create(SupplierData $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            return Supplier::create($data->toArray());
        });
    }

    /**
     * Update an existing supplier.
     * 
     * @param Supplier $supplier Supplier to update
     * @param SupplierData $data Data to update
     * @return Supplier Updated supplier
     */
    public function update(Supplier $supplier, SupplierData $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update($data->toArray());
            return $supplier->fresh();
        });
    }

    /**
     * Delete a supplier.
     * 
     * @param Supplier $supplier Supplier to delete
     */
    public function delete(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            $supplier->delete();
        });
        Notification::make()
            ->title('Tedarikçi silindi')
            ->success()
            ->send();
    }
}