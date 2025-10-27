<?php

declare(strict_types=1);

namespace App\Services\User\Contracts;

use App\Models\User;
use App\DTOs\User\UserData;
use App\DTOs\User\UserUpdateData;
use App\DTOs\User\UserLoginData;
use App\DTOs\User\UserPasswordResetData;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * User service interface
 * 
 * Defines the methods required for managing user operations.
 * Handles user creation, updating, deletion, authentication, and password management.
 */
interface UserServiceInterface
{
    /**
     * Create a new user.
     * 
     * @param UserData $data User data
     * @return User Created user
     */
    public function create(UserData $data): User;

    /**
     * Update an existing user.
     * 
     * @param User $user User to update
     * @param UserData $data New user data
     * @return User Updated user
     */
    public function update(User $user, UserData $data): User;

    /**
     * Delete a user.
     * 
     * @param User $user User to delete
     * @param bool $shouldNotify Whether to show a notification
     */
    public function delete(User $user, bool $shouldNotify = true): void;

    /**
     * Restore a deleted user.
     * 
     * @param User $user User to restore
     * @param bool $shouldNotify Whether to show a notification
     * @return User Restored user
     */
    public function restore(User $user, bool $shouldNotify = true): User;

    /**
     * Update a user's password.
     * 
     * @param User $user User to update password
     * @param string $password New password
     * @return User Updated user
     */
    public function updatePassword(User $user, string $password): User;


    /**
     * Login a user.
     * 
     * @param UserLoginData $data Login data
     * @return Authenticatable|null Logged in user or null
     */
    public function login(UserLoginData $data): ?Authenticatable;
} 