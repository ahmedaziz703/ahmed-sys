<?php

declare(strict_types=1);

namespace App\Services\User\Implementations;

use App\Models\User;
use App\Services\User\Contracts\UserServiceInterface;
use App\DTOs\User\UserData;
use App\DTOs\User\UserUpdateData;
use App\DTOs\User\UserLoginData;
use App\DTOs\User\UserPasswordResetData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Str;

/**
 * User service implementation
 * 
 * Contains methods required to manage user operations.
 * Handles user creation, updating, deletion, authentication, and password management.
 * Handles user commission management.
 */
class UserService implements UserServiceInterface
{
    /**
     * Create a new user.
     * 
     * @param UserData $data User data
     * @return User Created user
     */
    public function create(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            // Kullanıcıyı أنشاء
            $user = User::create($data->toModelData());
            
            // Eğer rol bilgisi varsa rolleri ata
            if (!empty($data->roles)) {
                $user->assignRole($data->roles);
            }
            
            return $user;
        });
    }

    /**
     * Update an existing user.
     * 
     * @param User $user User to update
     * @param UserData $data New user data
     * @return User Updated user
     */
    public function update(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Update the user
            $user->update($data->toModelData());
            
            // If role information exists, update the roles
            if ($data->roles !== null) {
                $user->syncRoles($data->roles);
            }
            
            return $user->fresh();
        });
    }

    /**
     * Delete a user.
     * 
     * @param User $user User to delete
     * @param bool $shouldNotify Whether to show a notification
     */
    public function delete(User $user, bool $shouldNotify = true): void
    {
        DB::transaction(function () use ($user, $shouldNotify) {
            // Delete the user (soft delete)
            $user->delete();

            // If notification is requested, show a notification
            if ($shouldNotify) {
                Notification::make('user-deleted')
                    ->title('Kullanıcı silindi')
                    ->success()
                    ->send();
            }
        });
    }

    /**
     * Restore a deleted user.
     * 
     * @param User $user User to restore
     * @param bool $shouldNotify Whether to show a notification
     * @return User Restored user
     */
    public function restore(User $user, bool $shouldNotify = true): User
    {
        return DB::transaction(function () use ($user, $shouldNotify) {
            // Restore the user
            $user->restore();
            
            // If notification is requested, show a notification
            if ($shouldNotify) {
                Notification::make()
                    ->title('Kullanıcı geri yüklendi')
                    ->success()
                    ->send();
            }
            
            return $user->fresh();
        });
    }

    /**
     * Update a user's password.
     * 
     * @param User $user User to update password
     * @param string $password New password
     * @return User Updated user
     */
    public function updatePassword(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password) {
            $user->update([
                'password' => Hash::make($password),
            ]);
            
            return $user->fresh();
        });
    }

    /**
     * Login a user.
     * 
     * @param UserLoginData $data Login data
     * @return Authenticatable|null Logged in user or null
     */
    public function login(UserLoginData $data): ?Authenticatable
    {
        $credentials = $data->credentials();
        $remember = $data->remember_me;
        
        if (Auth::attempt($credentials, $remember)) {
            return Auth::user();
        }
        
        return null;
    }

    /**
     * Initiate a password reset.
     * 
     * @param string $email User email to reset password
     * @return bool Whether the operation was successful
     */
    public function initiatePasswordReset(string $email): bool
    {
        $status = Password::sendResetLink(['email' => $email]);
        
        return $status === Password::RESET_LINK_SENT;
    }

    /**
     * Reset a user's password.
     * 
     * @param UserPasswordResetData $data Password reset data
     * @return bool Whether the operation was successful
     */
    public function resetPassword(UserPasswordResetData $data): bool
    {
        $status = Password::reset(
            $data->credentials(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordResetEvent($user));
            }
        );
        
        return $status === Password::PASSWORD_RESET;
    }
} 