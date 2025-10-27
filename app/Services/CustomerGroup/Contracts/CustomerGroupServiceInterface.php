<?php

declare(strict_types=1);

namespace App\Services\CustomerGroup\Contracts;

use App\Models\CustomerGroup;
use App\DTOs\CustomerGroup\CustomerGroupData;

/**
 * Customer group service interface
 * 
 * Defines the methods required for managing customer group operations.
 * Handles customer group creation, updating, and deletion.
 */
interface CustomerGroupServiceInterface
{
    /**
     * Create a new customer group.
     * 
     * @param CustomerGroupData $data Customer group data
     * @return CustomerGroup Created customer group
     */
    public function create(CustomerGroupData $data): CustomerGroup;

    /**
     * Update an existing customer group.
     * 
     * @param CustomerGroup $customerGroup Customer group to update
     * @param CustomerGroupData $data New customer group data
     * @return CustomerGroup Updated customer group
     */
    public function update(CustomerGroup $customerGroup, CustomerGroupData $data): CustomerGroup;

    /**
     * Delete a customer group.
     * 
     * @param CustomerGroup $customerGroup Customer group to delete
     */
    public function delete(CustomerGroup $customerGroup): void;
} 