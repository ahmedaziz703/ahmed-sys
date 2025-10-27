<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

/**
 * Lead Data Transfer Object
 * 
 * Used to transfer and convert lead data.
 * Used for lead creation, updating, and viewing.
 */
class LeadData
{
    /**
     * @param string $name Lead name
     * @param string $type Lead type
     * @param string|null $email Email address
     * @param string|null $phone Phone number
     * @param string|null $city City
     * @param string|null $district District
     * @param string|null $address Address
     * @param string|null $notes Notes
     * @param string $source Source
     * @param string $status Status
     * @param int|null $assigned_to Assigned user ID
     * @param string|null $next_contact_date Next contact date
     * @param string|null $last_contact_date Last contact date
     * @param string|null $converted_at Converted at
     * @param int|null $converted_to_customer_id Converted customer ID
     * @param string|null $conversion_reason Conversion reason
     * @param int|null $user_id User ID
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly ?string $notes,
        public readonly string $source,
        public readonly string $status,
        public readonly ?int $assigned_to,
        public readonly ?string $next_contact_date,
        public readonly ?string $last_contact_date = null,
        public readonly ?string $converted_at = null,
        public readonly ?int $converted_to_customer_id = null,
        public readonly ?string $conversion_reason = null,
        public readonly ?int $user_id = null,
    ) {}

    /**
     * Create lead data from array
     * 
     * @param array $data Lead data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            address: $data['address'] ?? null,
            notes: $data['notes'] ?? null,
            source: $data['source'] ?? 'other',
            status: $data['status'] ?? 'new',
            assigned_to: $data['assigned_to'] ?? null,
            next_contact_date: $data['next_contact_date'] ?? null,
            last_contact_date: $data['last_contact_date'] ?? null,
            converted_at: $data['converted_at'] ?? null,
            converted_to_customer_id: $data['converted_to_customer_id'] ?? null,
            conversion_reason: $data['conversion_reason'] ?? null,
            user_id: $data['user_id'] ?? null,
        );
    }

    /**
     * Convert lead data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'notes' => $this->notes,
            'source' => $this->source,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'next_contact_date' => $this->next_contact_date,
            'last_contact_date' => $this->last_contact_date,
            'converted_at' => $this->converted_at,
            'converted_to_customer_id' => $this->converted_to_customer_id,
            'conversion_reason' => $this->conversion_reason,
            'user_id' => $this->user_id,
        ];
    }
} 