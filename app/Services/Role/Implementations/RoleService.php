<?php

declare(strict_types=1);

namespace App\Services\Role\Implementations;

use Spatie\Permission\Models\Role;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\DTOs\Role\RoleData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Role service implementation
 * 
 * Contains methods required to manage role operations.
 * Handles role creation, updating, and deletion.
 */
final class RoleService implements RoleServiceInterface
{
    /**
     * Create a new role.
     * 
     * @param RoleData $data Role data
     * @return Role Created role
     */
    public function create(RoleData $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data->name]);
            $role->syncPermissions($data->permissions ?? []);
            return $role;
        });
    }

    /**
     * Update an existing role.
     * 
     * @param Role $role Role to update
     * @param RoleData $data New role data
     * @return Role Updated role
     */
    public function update(Role $role, RoleData $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data->name]);
            $role->syncPermissions($data->permissions ?? []);
            return $role->fresh();
        });
    }

    /**
     * Delete a role.
     * 
     * @param Role $role Role to delete
     */
    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $role->delete();
        });
        Notification::make()
            ->title('Rol silindi')
            ->success()
            ->send();
    }
}