<?php

declare(strict_types=1);

namespace App\Services\Role\Contracts;

use Spatie\Permission\Models\Role;
use App\DTOs\Role\RoleData;

/**
 * Role service interface
 * 
 * Defines the methods required for managing role operations.
 * Handles role creation, updating, and deletion.
 */
interface RoleServiceInterface
{
    /**
     * Create a new role.
     * 
     * @param RoleData $data Role data
     * @return Role Created role
     */
    public function create(RoleData $data): Role;

    /**
     * Update an existing role.
     * 
     * @param Role $role Role to update
     * @param RoleData $data New role data
     * @return Role Updated role
     */
    public function update(Role $role, RoleData $data): Role;

    /**
     * Delete a role.
     * 
     * @param Role $role Role to delete
     */
    public function delete(Role $role): void;
}