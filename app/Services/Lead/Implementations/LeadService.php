<?php

declare(strict_types=1);

namespace App\Services\Lead\Implementations;

use App\Models\Lead;
use App\Models\Customer;
use App\Services\Lead\Contracts\LeadServiceInterface;
use App\DTOs\Lead\LeadData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Lead service implementation
 * 
 * Contains methods required to manage lead operations.
 * Handles creating, updating, deleting, and converting leads to customers.
 */
class LeadService implements LeadServiceInterface
{
    /**
     * Create a new lead record.
     * 
     * @param LeadData $data Lead data
     * @return Lead Created lead record
     */
    public function create(LeadData $data): Lead
    {
        return DB::transaction(function () use ($data) {
            return Lead::create($data->toArray());
        });
    }

    /**
     * Update an existing lead record.
     * 
     * @param Lead $lead Lead record to update
     * @param LeadData $data New lead data
     * @return Lead Updated lead record
     */
    public function update(Lead $lead, LeadData $data): Lead
    {
        return DB::transaction(function () use ($lead, $data) {
            $lead->update([
                'name' => $data->name,
                'type' => $data->type,
                'email' => $data->email,
                'phone' => $data->phone,
                'city' => $data->city,
                'district' => $data->district,
                'address' => $data->address,
                'notes' => $data->notes,
                'source' => $data->source,
                'status' => $data->status,
                'assigned_to' => $data->assigned_to,
                'next_contact_date' => $data->next_contact_date,
            ]);
            return $lead->fresh();
        });
    }

    /**
     * Delete a lead record.
     * 
     * @param Lead $lead Lead record to delete
     */
    public function delete(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            $lead->delete();
        });
    }

    /**
     * Convert a lead to a customer.
     * 
     * Converts a lead record to a customer record and updates the lead's status.
     * Shows a notification after conversion.
     * 
     * @param Lead $lead Lead record to convert
     * @param array $data Customer data
     */
    public function convertToCustomer(Lead $lead, array $data): void
    {
        DB::transaction(function () use ($lead, $data) {
            // Convert lead to customer
            $customer = Customer::create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'description' => $lead->notes,
                'type' => $data['type'],
                'tax_number' => $data['tax_number'] ?? null,
                'tax_office' => $data['tax_office'] ?? null,
                'customer_group_id' => $data['customer_group_id'],
                'status' => true,
                'user_id' => $lead->user_id, 
            ]);

            // Update lead
            $lead->update([
                'status' => 'converted',
                'converted_at' => now(),
                'converted_to_customer_id' => $customer->id,
                'conversion_reason' => $data['conversion_reason'] ?? null,
            ]);

            Notification::make()
                ->title('العميلye dönüştürüldü')
                ->success()
                ->send();
        });
    }
} 