<?php

namespace App\Livewire\Role;

use Livewire\Component;
use Filament\Forms;
use Filament\Tables;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Notifications\Notification;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\DTOs\Role\RoleData;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;

/**
 * Role Manager Component
 * 
 * This component provides functionality to manage roles.
 * Features:
 * - Role list view
 * - New role creation
 * - Role editing
 * - Role deletion
 * - Permission management
 * - Bulk operation support
 * 
 * @package App\Livewire\Role
 */
class RoleManager extends Component implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

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
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Role::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدور')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('عدد الصلاحيات')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('عدد المستخدمين')
                    ->sortable(),
            ])
            ->emptyStateHeading('لم يتم العثور على دور')
            ->emptyStateDescription('إنشاء دور جديد.')
            ->defaultSort('name')
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('إدارة الصلاحيات')
                    ->url(fn (Role $record): string => route('admin.roles.edit', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->visible(auth()->user()->can('roles.edit')),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->modalHeading('حذف الدور')
                    ->modalDescription('هل أنت متأكد من حذف هذا الدور؟')
                    ->modalSubmitActionLabel('نعم, حذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->requiresConfirmation()
                    ->action(function (Role $record) {
                        if ($record->name === 'admin') {
                            Notification::make()
                                ->title('لا يمكن حذف هذا الدور')
                                ->body('\'admin\' دور المدير محمي من قبل النظام ولا يمكن حذفه.')
                                ->danger()
                                ->send();
                            return;
                        }
                        if ($record->users->count() > 0) {
                            Notification::make()
                                ->title('هذا الدور يستخدم')
                                ->body('لأنه يوجد مستخدمون مخصصون لهذا الدور، لا يمكن حذفه.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $record->delete();
                        Notification::make()
                            ->title('تم حذف الدور بنجاح')
                            ->success()
                            ->send();
                    })
                    ->visible(auth()->user()->can('roles.delete')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('إنشاء دور جديد')
                    ->form([
                        TextInput::make('name')
                            ->label('اسم الدور')
                            ->required()
                            ->unique(table: Role::class, column: 'name')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data) {
                        $role = Role::create($data);
                        Notification::make()
                            ->title('تم إنشاء الدور بنجاح')
                            ->success()
                            ->send();
                        
                        return $this->redirectRoute('admin.roles.edit', ['role' => $role->id], navigate: true);
                    })
                    ->modalHeading('إنشاء دور جديد')
                    ->modalSubmitActionLabel('أنشاء')
                    ->modalCancelActionLabel('يلغي')
                    ->visible(auth()->user()->can('roles.create'))
            ]);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.role.manager');
    }
}