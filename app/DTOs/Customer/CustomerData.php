<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

/**
 * Customer Data Transfer Object
 * 
 * Used to transfer and convert customer data.
 * Used for customer creation, updating, and viewing.
 */
class CustomerData
{
    /**
     * @param string $name Customer name
     * @param string $type Customer type
     * @param string|null $email Email address
     * @param string|null $phone Phone number
     * @param string|null $tax_number Tax number
     * @param string|null $tax_office Tax office
     * @param string|null $city City
     * @param string|null $district District
     * @param string|null $address Address
     * @param string|null $description Description
     * @param bool $status Status
     * @param int|null $customer_group_id Customer group ID
     * @param int|null $user_id User ID
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $tax_number,
        public readonly ?string $tax_office,
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly ?string $description,
        public readonly bool $status,
        public readonly ?int $customer_group_id,
        public readonly ?int $user_id = null,
    ) {}

    /**
     * Create customer data from array
     * 
     * @param array $data Customer data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            tax_number: $data['tax_number'] ?? null,
            tax_office: $data['tax_office'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            address: $data['address'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? true,
            customer_group_id: isset($data['customer_group_id']) ? (int) $data['customer_group_id'] : null,
            user_id: $data['user_id'] ?? null,
        );
    }

    /**
     * Convert customer data to array
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
            'tax_number' => $this->tax_number,
            'tax_office' => $this->tax_office,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status,
            'customer_group_id' => $this->customer_group_id,
            'user_id' => $this->user_id,
        ];
    }
} 