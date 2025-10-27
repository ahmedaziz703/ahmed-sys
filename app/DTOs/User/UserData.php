<?php

declare(strict_types=1);

namespace App\DTOs\User;

/**
 * User Data Transfer Object
 * 
 * Used to transfer and convert user data.
 * Used for user creation, updating, and viewing.
 */
class UserData
{
    /**
     * @param string $name User name
     * @param string $email Email address
     * @param string|null $phone Phone number
     * @param string|null $password Password (for creation and updating)
     * @param bool $status Status
     * @param bool $has_commission Has commission?
     * @param float|null $commission_rate Commission rate
     * @param array|null $roles Roles array
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly ?string $password = null,
        public readonly bool $status = true,
        public readonly bool $has_commission = false,
        public readonly ?float $commission_rate = null,
        public readonly ?array $roles = null,
    ) {}

    /**
     * Create user data from array
     * 
     * @param array $data User data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
            status: $data['status'] ?? true,
            has_commission: $data['has_commission'] ?? false,
            commission_rate: isset($data['commission_rate']) ? (float) $data['commission_rate'] : null,
            roles: $data['roles'] ?? null,
        );
    }

    /**
     * Convert user data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
            'status' => $this->status,
            'has_commission' => $this->has_commission,
            'commission_rate' => $this->commission_rate,
            'roles' => $this->roles,
        ];
    }

    /**
     * Return the data for the user's model creation/update
     * If password is null, it is left outside (in update mode)
     * 
     * @return array
     */
    public function toModelData(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'has_commission' => $this->has_commission,
            'commission_rate' => $this->commission_rate,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        return $data;
    }
} 