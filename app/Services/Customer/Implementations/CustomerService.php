<?php

declare(strict_types=1);

namespace App\Services\Customer\Implementations;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\DTOs\Customer\CustomerData;
use App\DTOs\Customer\NoteData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Customer service implementation
 * 
 * Contains methods required to manage customer operations.
 * Handles creating, updating, deleting, and adding notes for customers.
 */
class CustomerService implements CustomerServiceInterface
{
    /**
     * Create a new customer.
     * 
     * @param CustomerData $data Customer data
     * @return Customer Created customer
     */
    public function create(CustomerData $data): Customer
    {
        return DB::transaction(function () use ($data) {
            return Customer::create($data->toArray());
        });
    }

    /**
     * Update an existing customer.
     * 
     * @param Customer $customer Customer to update
     * @param CustomerData $data New customer data
     * @return Customer Updated customer
     */
    public function update(Customer $customer, CustomerData $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update([
                'name' => $data->name,
                'type' => $data->type,
                'email' => $data->email,
                'phone' => $data->phone,
                'tax_number' => $data->tax_number,
                'tax_office' => $data->tax_office,
                'city' => $data->city,
                'district' => $data->district,
                'address' => $data->address,
                'description' => $data->description,
                'status' => $data->status,
                'customer_group_id' => $data->customer_group_id,
            ]);
            return $customer->fresh();
        });
    }

    /**
     * Delete the customer.
     * 
     * Deletes all notes belonging to the customer and optionally shows a notification.
     * 
     * @param Customer $customer Customer to delete
     * @param bool $shouldNotify Whether to show a notification
     */
    public function delete(Customer $customer, bool $shouldNotify = true): void
    {
        DB::transaction(function () use ($customer, $shouldNotify) {
            // Delete customer's notes
            $customer->notes()->delete();
            
            // Delete the customer
            $customer->delete();

            // If notification is requested
            if ($shouldNotify) {
                Notification::make()
                    ->title('العميل silindi')
                    ->success()
                    ->send();
            }
        });
    }

    /**
     * Add a note to the customer.
     * 
     * @param Customer $customer Customer to add note to
     * @param NoteData $data Note data
     * @return CustomerNote Created note
     */
    public function addNote(Customer $customer, NoteData $data): CustomerNote
    {
        return DB::transaction(function () use ($customer, $data) {
            return $customer->notes()->create($data->toArray());
        });
    }
} 