<?php

declare(strict_types=1);

namespace App\DTOs\User;

/**
 * User Login Data Transfer Object
 * 
 * Used to transfer and convert user login data.
 * Used for user login operations.
 */
class UserLoginData
{
    /**
     * @param string $email Email address
     * @param string $password Password
     * @param bool $remember_me Remember me option
     */
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember_me = false,
    ) {}

    /**
     * Create user login data from array
     * 
     * @param array $data Login data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            remember_me: $data['remember_me'] ?? false,
        );
    }

    /**
     * Convert user login data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'remember_me' => $this->remember_me,
        ];
    }

    /**
     * Return the data for the user's login credentials
     * 
     * @return array
     */
    public function credentials(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
} 