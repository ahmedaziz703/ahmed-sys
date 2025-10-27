<?php

declare(strict_types=1);

namespace App\Services\CustomerGroup\Implementations;

use App\Models\CustomerGroup;
use App\Services\CustomerGroup\Contracts\CustomerGroupServiceInterface;
use App\DTOs\CustomerGroup\CustomerGroupData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Customer group service implementation
 * 
 * Contains methods required to manage customer group operations.
 * Handles creating, updating, and deleting customer groups.
 */
class CustomerGroupService implements CustomerGroupServiceInterface
{
    /**
     * Create a new customer group.
     * 
     * @param CustomerGroupData $data Customer group data
     * @return CustomerGroup Created customer group
     */
    public function create(CustomerGroupData $data): CustomerGroup
    {
        return DB::transaction(function () use ($data) {
            return CustomerGroup::create($data->toArray());
        });
    }

    /**
     * Update an existing customer group.
     * 
     * @param CustomerGroup $customerGroup Customer group to update
     * @param CustomerGroupData $data New customer group data
     * @return CustomerGroup Updated customer group
     */
    public function update(CustomerGroup $customerGroup, CustomerGroupData $data): CustomerGroup
    {
        return DB::transaction(function () use ($customerGroup, $data) {
            $customerGroup->update([
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
            ]);
            return $customerGroup->fresh();
        });
    }

    /**
     * Delete a customer group.
     * 
     * Shows a success notification after deletion.
     * 
     * @param CustomerGroup $customerGroup Customer group to delete
     */
    public function delete(CustomerGroup $customerGroup): void
    {
        DB::transaction(function () use ($customerGroup) {
            $customerGroup->delete();
        });

        Notification::make()
        ->title('مجموعه العميل silindi')
        ->success()
        ->send();
    }
} 