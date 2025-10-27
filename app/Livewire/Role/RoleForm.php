<?php

namespace App\Livewire\Role;

use App\DTOs\Role\RoleData;
use App\Services\Role\Contracts\RoleServiceInterface;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;

/**
 * Role Form Component
 * 
 * This component provides a form for creating and editing roles.
 * Features:
 * - Role creation
 * - Role editing
 * - Role deletion
 * - Permission management
 * - Bulk operation support
 */
class RoleForm extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    /** @var Role|null Edited role */
    public ?Role $role = null;

    /** @var bool Editing mode status */
    public bool $isEdit = false;

    /** @var array Form data */
    public $formData = [];

    /** @var RoleServiceInterface Role service */
    private RoleServiceInterface $roleService;

    /**
     * When the component is booted, the role service is injected
     * 
     * @param RoleServiceInterface $roleService Role service
     * @return void
     */
    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    /**
     * When the component is mounted, the role is loaded
     * 
     * @param Role|null $role Role to edit
     * @return void
     */
    public function mount(Role $role = null): void
    {
        $this->isEdit = $role !== null;

        if ($this->isEdit) {
            $this->role = Role::with('permissions')->findOrFail($role->id);
            $this->createEditForm();
        } else {
            $this->role = new Role();
            $this->createNewForm();
        }
    }

    /**
     * Creates the edit form
     * 
     * @return void
     */
    private function createEditForm(): void
    {
        $rolePermissions = $this->role->permissions->pluck('name')->toArray();
        $this->formData = [
            'name' => $this->role->name,
        ];

        $groups = $this->getPermissionGroups();

        // Check all permission groups to ensure completeness
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                // Category with sub-groups
                foreach ($groupData as $subGroupName => $permissions) {
                    if (!empty($permissions) && is_array($permissions)) {
                        $key = "permissions_{$groupName}_{$subGroupName}";
                        $groupPermissions = array_keys($permissions);
                        $selectedInGroup = array_intersect($rolePermissions, $groupPermissions);
                        $this->formData[$key] = array_values($selectedInGroup); // For CheckboxList
                    } else {
                        $this->formData["permissions_{$groupName}_{$subGroupName}"] = [];
                    }
                }
            } else {
                // Single level category
                $key = "permissions_{$groupName}";
                $permissionsInGroup = is_array($groupData) ? $groupData : [];

                if (!empty($permissionsInGroup)) {
                    $groupPermissionNames = array_keys($permissionsInGroup);
                    $selectedInGroup = array_intersect($rolePermissions, $groupPermissionNames);
                    $this->formData[$key] = array_values($selectedInGroup); // For CheckboxList
                } else {
                    $this->formData[$key] = [];
                }
            }
        }

        $this->form->fill($this->formData);
    }

    /**
     * Creates the new form
     * 
     * @return void
     */
    private function createNewForm(): void
    {
        $this->formData = ['name' => ''];
        $groups = $this->getPermissionGroups();

        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                foreach ($groupData as $subGroupName => $permissions) {
                    $key = "permissions_{$groupName}_{$subGroupName}";
                    $this->formData[$key] = [];
                }
            } else {
                $key = "permissions_{$groupName}";
                $this->formData[$key] = [];
            }
        }

        $this->form->fill($this->formData);
    }

    /**
     * Gets the permission groups
     * 
     * @return array Permission groups
     */
    protected function getPermissionGroups(): array
    {
        $permissions = Permission::all();
        $groups = [];

        $groups['إدارة العملاء']['العملاء'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'customers.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();
            
        $groups['إدارة العملاء']['مجموعات العملاء'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'customer_groups.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة العملاء']['العملاء المحتملين'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'leads.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الحسابات']['حسابات البنك'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'bank_accounts.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الحسابات']['بطاقات الائتمان'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'credit_cards.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الحسابات']['محفظة العملات المشفرة'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'crypto_wallets.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الحسابات']['POS الافتراضي'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'virtual_pos.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة المعاملات']['المعاملات والتحويلات'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'transactions.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة المعاملات']['المعاملات المستمرة'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'recurring_transactions.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة المعاملات']['معاملات القروض'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'loans.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة المعاملات']['الديون/القروض'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'debts.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الاستثمارات']['خطط الاستثمارات'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'investments.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الاستثمارات']['خطط التوفير'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'savings.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة التحليل والتقارير']['تدفق النقدية'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'reports.cash_flow'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();   

        $groups['إدارة التحليل والتقارير']['تحليل الفئات'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'reports.category_analysis'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة النظام']['الإعدادات'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'settings.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة النظام']['الأدوار'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'roles.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة النظام']['المستخدمين'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'users.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();
        
        $groups['إدارة الموردين']['الموردين'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'suppliers.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة المشاريع']['المشاريع'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'projects.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['إدارة الفئات']['الفئات'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'categories.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();
        

        // Clean empty groups
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData)) {
                foreach ($groupData as $subGroupName => $permissions) {
                    if (empty($permissions)) {
                        unset($groups[$groupName][$subGroupName]);
                    }
                }
                if (empty($groups[$groupName])) {
                    unset($groups[$groupName]);
                }
            } elseif (empty($groupData)) {
                unset($groups[$groupName]);
            }
        }

        return $groups;
    }

    /**
     * Creates the form
     * 
     * @param Forms\Form $form Form object
     * @return Forms\Form Configured form
     */
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Rol Adı')
                    ->required()
                    ->maxLength(255),
                ...$this->buildPermissionGroups()
            ])
            ->statePath('formData');
    }

    /**
     * Builds the permission groups
     * 
     * @return array Permission groups
     */
    protected function buildPermissionGroups(): array
    {
        $groups = $this->getPermissionGroups();
        $components = [];
        $firstGroup = true; // Flag to track the first group

        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                $subComponents = [];
                foreach ($groupData as $subGroupName => $permissions) {
                    if (!empty($permissions) && is_array($permissions)) {
                        $subComponents[] = Section::make()
                            ->heading($subGroupName)
                            ->schema([
                                CheckboxList::make("permissions_{$groupName}_{$subGroupName}")
                                    ->label('')
                                    ->options($permissions)
                                    ->bulkToggleable()
                                    ->columns(2)
                            ]);
                    }
                }
                if (!empty($subComponents)) {
                    $components[] = Section::make($groupName)
                        ->schema($subComponents)
                        ->collapsible()
                        ->collapsed(!$firstGroup); // If not the first group, collapse
                    $firstGroup = false; // First group processed
                }
            } else {
                $permissionsInGroup = is_array($groupData) ? $groupData : [];
                $optionsKey = "permissions_{$groupName}";

                if (!empty($permissionsInGroup)) {
                    $components[] = Section::make()
                        ->heading($groupName)
                        ->schema([
                            CheckboxList::make($optionsKey)
                                ->label('')
                                ->options($permissionsInGroup)
                                ->bulkToggleable()
                                ->columns(2)
                        ])
                        ->collapsible()
                        ->collapsed(!$firstGroup); // If not the first group, collapse
                    $firstGroup = false; // First group processed
                }
            }
        }

        return $components;
    }

    /**
     * Saves the form
     * 
     * @return void
     */
    public function save(): void
    {
        $data = $this->form->getState();
        $selectedPermissions = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $selectedPermissions = array_merge($selectedPermissions, $value);
            }
        }

        $roleData = RoleData::fromArray([
            'name' => $data['name'],
            'permissions' => array_unique($selectedPermissions)
        ]);

        try {
            if ($this->isEdit) {
                $this->roleService->update($this->role, $roleData);
                Notification::make()
                    ->title('تم تحديث الدور بنجاح')
                    ->success()
                    ->send();
            } else {
                $this->roleService->create($roleData);
                Notification::make()
                    ->title('تم إنشاء الدور بنجاح')
                    ->success()
                    ->send();
            }

            $this->redirectRoute('admin.roles.index', navigate: true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('خطأ أثناء إنشاء الدور: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Cancels the form
     * 
     * @return void
     */
    public function cancel(): void
    {
        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.role.role-form');
    }
}