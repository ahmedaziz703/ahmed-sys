<?php

declare(strict_types=1);

namespace App\DTOs\CustomerGroup;

/**
 * Customer Group Data Transfer Object
 * 
 * Used to transfer and convert customer group data.
 * Used for customer group creation, updating, and viewing.
 */
class CustomerGroupData
{
    /**
     * @param string $name Name
     * @param string|null $description Description
     * @param bool $status Status
     * @param int|null $user_id User ID
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $status,
        public readonly ?int $user_id,
    ) {}

    /**
     * Create customer group data from array
     * 
     * @param array $data Customer group data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'] ?? true,
            user_id: $data['user_id'],
        );
    }

    /**
     * Convert customer group data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'user_id' => $this->user_id,
        ];
    }
} 