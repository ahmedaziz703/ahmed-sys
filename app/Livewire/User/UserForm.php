<?php

namespace App\Livewire\User;

use App\DTOs\User\UserData;
use App\Models\User;
use App\Services\User\Contracts\UserServiceInterface;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * User creation and editing form component.
 * 
 * This component provides a form interface for creating and editing user information
 * using the Filament Form API. Basic user information, role assignments, and commission settings
 * are managed through this form.
 */
class UserForm extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var User|null Edited user */
    public ?User $user = null;
    
    /** @var bool Edit mode */
    public bool $isEdit = false;
    
    /** @var array User roles */
    public array $roles = [];
    
    /** @var bool Commission usage */
    public bool $hasCommission = false;
    
    /** @var float|null Commission rate */
    public ?float $commissionRate = null;
    
    /** @var array Form data */
    public $data = [];
    
    /** @var User|null User to be restored */
    public ?User $deletedUser = null;

    /** @var bool Show/hide restore modal */
    public bool $showRestoreModal = false;

    /**
     * Displays permissions in a modal
     */
    public $showPermissionsModal = false;
    public $selectedRoleId = null;
    public $permissionsList = [];

    /**
     * Prepares the form and loads existing user data if available
     */
    public function mount($user = null): void
    {
        // Edit mode check
        $this->isEdit = $user !== null;
        
        // If edit mode, load user and roles
        if ($this->isEdit) {
            $this->user = User::with('roles')->find($user->id);
        } else {
            $this->user = new User();
        }
        
        // Render forms separately
        if ($this->isEdit) {
            $this->createEditForm();
        } else {
            $this->createNewForm();
        }
    }
    
    /**
     * Creates the edit form (without password)
     */
    private function createEditForm(): void
    {
        // Get user information and role ID
        $role = $this->user->roles->first();
        $roleId = $role ? $role->id : null;

        // Prepare data for form filling
        $formData = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'status' => $this->user->status,
            'roles' => $roleId,
            'has_commission' => $this->user->has_commission ?? false,
            'commission_rate' => $this->user->commission_rate,
        ];

        // Fill form with prepared data
        $this->form->fill($formData);
    }
    
    /**
     * Creates the new user form (with password)
     */
    private function createNewForm(): void
    {
        $this->form->fill([
            'name' => '',
            'email' => '',
            'phone' => '',
            'password' => '', // Only in new user form
            'status' => 1,
            'roles' => null,
            'has_commission' => false,
            'commission_rate' => null,
        ]);
    }

    /**
     * Defines the form schema
     */
    public function form(Form $form): Form
    {
        // Basic fields - common for both forms
        $schema = [
            Section::make('معلومات المستخدم')
                ->columns(2)
                ->schema($this->getUserFieldsSchema()),
            
            Section::make('الحالة والأدوار')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            1 => 'نشط',
                            0 => 'معطل',
                        ])
                        ->native(false)
                        ->required()
                        ->default(1),
                    
                    
                    Select::make('roles')
                        ->label('الدور')
                        ->preload()
                        ->options(Role::pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->reactive(),
                    
                    Placeholder::make('permissions_info')
                        ->label('الصلاحيات')
                        ->content(function (Get $get) {
                            $roleId = $get('roles');
                            if (empty($roleId)) {
                                return 'الدور غير محدد.';
                            }
                            
                            $role = Role::find($roleId);
                            if (!$role || $role->permissions->isEmpty()) {
                                return 'لا يوجد صلاحيات لهذا الدور.';
                            }
                            
                            return view('livewire.user.partials.permissions-button', [
                                'roleId' => $roleId,
                                'count' => $role->permissions->count(),
                            ]);
                        })
                        ->columnSpanFull(),
                ]),
            
            Section::make('معلومات العمولة')
                ->columns(2)
                ->schema([
                    Toggle::make('has_commission')
                        ->label('مستخدم العمولة')
                        ->reactive()
                        ->default(false),
                    
                    TextInput::make('commission_rate')
                        ->label('نسبة العمولة (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->visible(fn (callable $get) => $get('has_commission')),
                ]),
        ];
        
        return $form->schema($schema)->statePath('data');
    }
    
    /**
     * Returns the user information fields
     * Contains different fields for edit and create mode
     */
    private function getUserFieldsSchema(): array
    {
        // Basic fields (common for both forms)
        $fields = [
            TextInput::make('name')
                ->label('اسم المستخدم')
                ->required()
                ->maxLength(255),
            
            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(
                    table: User::class, 
                    column: 'email', 
                    ignorable: $this->user,
                    modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')
                ),
            
            TextInput::make('phone')
                ->label('الهاتف')
                ->tel()
                ->maxLength(255),
        ];
        
        // Only add password field in create mode
        if (!$this->isEdit) {
            $fields[] = TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->required()
                ->rule(Password::default());
        }
        
        return $fields;
    }
    
    /**
     * Saves the form data
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Prepare the role data in the correct format
            $roleId = $data['roles'] ?? null;
            
            // Get the role correctly - use names
            $roles = [];
            if ($roleId) {
                // Find the role object using the role ID
                $role = Role::find($roleId);
                if ($role) {
                    $roles = [$role->name]; // Use name - Spatie\Permission expects name
                }
            }
            
            // Set the commission rate - if the commission user is not set, it should be 0
            $commissionRate = $data['has_commission'] ? ($data['commission_rate'] ?? 0) : 0;
            
            $userService = app(UserServiceInterface::class);
            
            // Check if there is a deleted user with the same email in new records
            if (!$this->isEdit) {
                // Find the deleted user with the same email
                $this->deletedUser = User::onlyTrashed()->where('email', $data['email'])->first();

                if ($this->deletedUser) {
                    // If the deleted user exists, show the modal and stop the operation
                    $this->showRestoreModal = true;
                    return;
                }
                
                // If the deleted user does not exist, create a new user
                $userData = new UserData(
                    name: $data['name'],
                    email: $data['email'],
                    phone: $data['phone'],
                    password: $data['password'], // Create mode has password
                    status: $data['status'],
                    has_commission: $data['has_commission'],
                    commission_rate: $commissionRate,
                    roles: $roles
                );
                
                $userService->create($userData);
                
                Notification::make('user-created')
                    ->title('تم إنشاء المستخدم بنجاح')
                    ->success()
                    ->send();
            } else {
                // Update the existing user
                $userData = new UserData(
                    name: $data['name'],
                    email: $data['email'],
                    phone: $data['phone'], 
                    password: null, // Edit mode has no password
                    status: $data['status'],
                    has_commission: $data['has_commission'],
                    commission_rate: $commissionRate,
                    roles: $roles
                );
                
                $userService->update($this->user, $userData);
                
                Notification::make('user-updated')
                    ->title('تم تحديث المستخدم بنجاح')
                    ->success()
                    ->send();
            }
            
            $this->redirectRoute('admin.users.index', navigate: true);
        } catch (\Exception $e) {
            Notification::make('user-operation-error')
                ->title('خطأ!')
                ->body('خطأ أثناء عملية المستخدم: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Confirms and performs the user restore operation.
     */
    public function confirmRestore(): void
    {
        if (!$this->deletedUser) {
            return; // If there is no user to restore, exit
        }

        try {
            $data = $this->form->getState(); // Get the current data from the form

            // Prepare the role data in the correct format
            $roleId = $data['roles'] ?? null;
            $roles = [];
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $roles = [$role->name];
                }
            }

            // Komisyon oranını ayarla
            $commissionRate = $data['has_commission'] ? ($data['commission_rate'] ?? 0) : 0;

            $userService = app(UserServiceInterface::class);

            // 1. Restore the user (without notification)
            $userService->restore($this->deletedUser, false);

            // 2. Update the user with the new information from the form
            $userData = new UserData(
                name: $data['name'],
                email: $data['email'],
                phone: $data['phone'],
                password: null, // When restoring, the password is not updated (can be added if needed)
                status: $data['status'],
                has_commission: $data['has_commission'],
                commission_rate: $commissionRate,
                roles: $roles
            );
             // IMPORTANT: $this->deletedUser reference can change after restore, find it again with ID
            $restoredUser = User::find($this->deletedUser->id);
            if ($restoredUser) {
                 $userService->update($restoredUser, $userData);
            } else {
                 // Error state - user not found
                 throw new \Exception("المستخدم المستعاد غير موجود.");
            }


            Notification::make('user-restored')
                ->title('تم استعادة المستخدم وتحديثه')
                ->success()
                ->send();

            $this->showRestoreModal = false; // Close the modal
            $this->redirectRoute('admin.users.index', navigate: true);

        } catch (\Exception $e) {
            Notification::make('user-restore-error')
                ->title('خطأ في إعادة التحميل!')
                ->body('خطأ أثناء إعادة التحميل/تحديث المستخدم: ' . $e->getMessage())
                ->danger()
                ->send();
             $this->showRestoreModal = false; // In case of error, close the modal
        }
    }

    /**
     * Cancels the user restore operation and closes the modal.
     */
    public function cancelRestore(): void
    {
        $this->showRestoreModal = false;
        $this->deletedUser = null; // When cancelled, clear the reference
    }

    /**
     * Cancel and return to the user list
     */
    public function cancel(): void
    {
        $this->redirectRoute('admin.users.index', navigate: true);
    }

    /**
     * Displays permissions in a modal
     */
    public function showPermissions($roleId): void
    {
        $this->selectedRoleId = $roleId;
        
        $role = Role::with('permissions')->find($roleId);
        if ($role) {
            // Prepare the permissions with the display_name in Turkish
            $this->permissionsList = $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? $permission->name, // Use display_name directly
                ];
            })->sortBy('display_name')->values()->toArray();
        }
        
        $this->showPermissionsModal = true;
    }

    public function closePermissionsModal(): void
    {
        $this->showPermissionsModal = false;
    }

    /**
     * Renders the component view
     */
    public function render()
    {
        return view('livewire.user.user-form');
    }
} 