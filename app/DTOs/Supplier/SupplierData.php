<?php

declare(strict_types=1);

namespace App\DTOs\Supplier;

/**
 * Supplier Data Transfer Object
 * 
 * Used to transfer and convert supplier data.
 * Used for supplier creation, updating, and viewing.
 */
class SupplierData
{
    /**
     * @param string $name Supplier name
     * @param string|null $contact_name Contact name
     * @param string|null $phone Phone number
     * @param string|null $email Email address
     * @param string|null $address Address
     * @param string|null $notes Notes
     * @param bool $status Status
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $contact_name,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly ?string $notes,
        public readonly bool $status,
    ) {}

    /**
     * Create supplier data from array
     * 
     * @param array $data Supplier data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            contact_name: $data['contact_name'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            address: $data['address'] ?? null,
            notes: $data['notes'] ?? null,
            status: $data['status'] ?? true,
        );
    }

    /**
     * Convert supplier data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}