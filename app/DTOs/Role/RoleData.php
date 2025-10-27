<?php

declare(strict_types=1);

namespace App\DTOs\Role;

/**
 * Role Data Transfer Object
 * 
 * Used to transfer and convert role data.
 * Used for role creation, updating, and viewing.
 * Contains permissions for role management.
 */
class RoleData
{
    /**
     * @param string $name Role name
     * @param array|null $permissions Permissions array
     */
    public function __construct(
        public readonly string $name,
        public readonly ?array $permissions = [],
    ) {}

    /**
     * Create role data from array
     * 
     * @param array $data Role data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            permissions: $data['permissions'] ?? [],
        );
    }

    /**
     * Convert role data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'permissions' => $this->permissions,
        ];
    }
}