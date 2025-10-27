<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Services\User\Contracts\UserServiceInterface;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Commission;
use App\Services\Commission\CommissionService;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Carbon;

/**
 * Component for managing users.
 * 
 * This component provides a list and CRUD interface for managing users
 * using the Filament Table API. Basic user operations
 * (edit, delete, restore) and bulk operations are managed through this component.
 */ 
class UserManager extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    /**
     * Defines the table configuration
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with('roles'))
            ->emptyStateHeading('لم يتم العثور على مستخدم')
            ->emptyStateDescription('أنشاء مستخدم جديد.')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('roles.name')
                    ->label('الدورات')
                    ->formatStateUsing(fn ($state, User $record) => $record->roles->pluck('name')->implode('، '))
                    ->searchable(),
                
                IconColumn::make('status')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('commission_rate')
                    ->label('العموله (%)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '-'),
            ])
            ->filters([
                TernaryFilter::make('status')
                    ->label('الحالة')
                    ->placeholder('نوع')
                    ->trueLabel('نشط')
                    ->falseLabel('الغاء')
                    ->native(false)
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 1),
                        false: fn (Builder $query) => $query->where('status', 0),
                        blank: fn (Builder $query) => $query
                    ),
                SelectFilter::make('roles')
                    ->label('Roller')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),

            ])
            ->actions([
                                

                Action::make('commission_history')
                    ->label('عرض التقارير للمستخدم')
                    ->icon('heroicon-m-currency-dollar')
                    ->url(fn (User $record) => route('admin.users.commissions', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->visible(fn (User $record) => $record->has_commission && auth()->user()->can('users.commissions')),

                
                Action::make('changePassword')
                    ->label('تغيير كلمة المرور')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('جديد Şifre')
                            ->password()
                            ->required()
                            ->confirmed()
                            ->rule(\Illuminate\Validation\Rules\Password::default()),
                        
                        \Filament\Forms\Components\TextInput::make('password_confirmation')
                            ->label('جديد Şifre Tekrar')
                            ->password()
                            ->required(),
                    ])
                    ->modalHeading('تغيير كلمة المرور')
                    ->modalDescription(fn (User $record) => "{$record->name} مستخدم لديه كلمة المرور الجديدة.")
                    ->action(function (User $record, array $data) {
                        try {
                            app(UserServiceInterface::class)->updatePassword($record, $data['password']);
                            
                            Notification::make('password-changed')
                                ->title('تم تغيير كلمة المرور بنجاح')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make('password-change-error')
                                ->title('خطأ!')
                                ->body('خطأ أثناء تغيير كلمة المرور: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->disabled(fn () => config('app.app_demo_mode', false))
                    ->tooltip(fn () => config('app.app_demo_mode', false) ? 'Demo modunda تغيير كلمة المرورilemez' : null)
                    ->visible(auth()->user()->can('users.change_password')),

                Action::make('edit')
                    ->label('تعديل')
                    ->url(fn (User $record) => route('admin.users.edit', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->icon('heroicon-m-pencil-square')
                    ->visible(auth()->user()->can('users.edit')),

                
                Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف المستخدم')
                    ->modalDescription('هل أنت متأكد من حذف هذا المستخدم؟')
                    ->action(function (User $record) {
                        try {
                            app(UserServiceInterface::class)->delete($record, true);
                            
                            Notification::make('user-deleted')
                                ->title('تم حذف المستخدم بنجاح')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make('user-delete-error')
                                ->title('خطأ!')
                                ->body('خطأ أثناء حذف المستخدم: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->hidden(fn (User $record) => $record->trashed())
                    ->visible(auth()->user()->can('users.delete')),

            ])
            ->headerActions([
                Action::make('create')
                    ->label('إنشاء مستخدم جديد')
                    ->extraAttributes(['wire:navigate' => true])
                    ->url(route('admin.users.create'))
                    ->visible(auth()->user()->can('users.create')),
            ]);
    }


    /**
     * Renders the component view
     */
    public function render()
    {
        return view('livewire.user.user-manager');
    }
} 