<?php

declare(strict_types=1);

namespace App\Services\Lead\Contracts;

use App\Models\Lead;
use App\DTOs\Lead\LeadData;

/**
 * Lead service interface
 * 
 * Defines the methods required for managing lead operations.
 * Handles lead creation, updating, deletion, and conversion to customer.
 */
interface LeadServiceInterface
{
    /**
     * Create a new lead record.
     * 
     * @param LeadData $data Lead data
     * @return Lead Created lead record
     */
    public function create(LeadData $data): Lead;

    /**
     * Update an existing lead record.
     * 
     * @param Lead $lead Lead record to update
     * @param LeadData $data New lead data
     * @return Lead Updated lead record
     */
    public function update(Lead $lead, LeadData $data): Lead;

    /**
     * Delete a lead record.
     * 
     * @param Lead $lead Lead record to delete
     */
    public function delete(Lead $lead): void;

    /**
     * Convert a lead to a customer.
     * 
     * Converts a lead record to a customer record and updates the lead's status.
     * Shows a notification after conversion.
     * 
     * @param Lead $lead Lead record to convert
     * @param array $data Customer data
     */
    public function convertToCustomer(Lead $lead, array $data): void;
} 