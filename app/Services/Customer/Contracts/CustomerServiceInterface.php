<?php

declare(strict_types=1);

namespace App\Services\Customer\Contracts;

use App\Models\Customer;
use App\DTOs\Customer\CustomerData;
use App\DTOs\Customer\NoteData;
use App\Models\CustomerNote;

/**
 * Customer service interface
 * 
 * Defines the methods required for managing customer operations.
 * Handles customer creation, updating, deletion, and note addition.
 */
interface CustomerServiceInterface
{
    /**
     * Create a new customer.
     * 
     * @param CustomerData $data Customer data
     * @return Customer Created customer
     */
    public function create(CustomerData $data): Customer;

    /**
     * Update an existing customer.
     * 
     * @param Customer $customer Customer to update
     * @param CustomerData $data New customer data
     * @return Customer Updated customer
     */
    public function update(Customer $customer, CustomerData $data): Customer;

    /**
     * Delete a customer.
     * 
     * @param Customer $customer Customer to delete
     * @param bool $shouldNotify Whether to show a notification
     */
    public function delete(Customer $customer, bool $shouldNotify = true): void;

    /**
     * Add a note to a customer.
     * 
     * @param Customer $customer Customer to add note to
     * @param NoteData $data Note data
     * @return CustomerNote Created note
     */
    public function addNote(Customer $customer, NoteData $data): CustomerNote;
} 