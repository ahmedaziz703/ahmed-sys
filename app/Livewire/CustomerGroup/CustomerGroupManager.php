<?php

namespace App\Livewire\CustomerGroup;

use App\Models\CustomerGroup;
use App\Services\CustomerGroup\Contracts\CustomerGroupServiceInterface;
use App\DTOs\CustomerGroup\CustomerGroupData;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;

/**
 * Customer Group Manager Component
 * 
 * This component provides functionality to manage customer groups.
 * Features:
 * - Customer group list view
 * - Create new customer group
 * - Edit customer group
 * - Delete customer group
 * - Customer group status management
 * 
 * @package App\Livewire\CustomerGroup
 */
final class CustomerGroupManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var CustomerGroupServiceInterface Customer group service */
    private CustomerGroupServiceInterface $customerGroupService;

    /**
     * When the component is booted, the customer group service is injected
     * 
     * @param CustomerGroupServiceInterface $customerGroupService Customer group service
     * @return void
     */
    public function boot(CustomerGroupServiceInterface $customerGroupService): void 
    {
        $this->customerGroupService = $customerGroupService;
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
            ->query(CustomerGroup::query())
            ->emptyStateHeading(' أنشاء مجموعة العميل')
            ->emptyStateDescription(' أنشاء مجموعة العميل')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المجموعة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('توضيح')
                    ->limit(50),
                Tables\Columns\IconColumn::make('status')
                    ->label('الوصف')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('أنشاء')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Grubu تعديل')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('مجموعه العميل تعديلndi')
                    ->visible(fn () => auth()->user()->can('customer_groups.edit'))
                    ->using(function (CustomerGroup $record, array $data): CustomerGroup {
                        $groupData = CustomerGroupData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerGroupService->update($record, $groupData);
                    })
                    ->form($this->getCustomerGroupForm()),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Grubu Sil')
                    ->modalDescription('Bu grubu silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('مجموعه العميل silindi')
                    ->visible(fn () => auth()->user()->can('customer_groups.delete'))
                    ->using(function (CustomerGroup $record): void {
                        $this->customerGroupService->delete($record);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(' أنشاء مجموعة العميل')
                    ->modalHeading(' أنشاء مجموعة العميل')
                    ->modalSubmitActionLabel('يحفظ')
                    ->modalCancelActionLabel('يلغي')
                    ->createAnother(false)
                    ->successNotificationTitle(' أنشاء مجموعة العميل')
                    ->visible(fn () => auth()->user()->can('customer_groups.create'))
                    ->using(function (array $data): CustomerGroup {
                        $groupData = CustomerGroupData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerGroupService->create($groupData);
                    })
                    ->form($this->getCustomerGroupForm()),
            ]);
    }

    /**
     * Creates the customer group form
     * 
     * @return array Form components
     */
    protected function getCustomerGroupForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('اسم المجموعة')
                ->required(),
            Forms\Components\Textarea::make('description')
                ->label('يوضح')
                ->rows(3),
            Forms\Components\Toggle::make('status')
                ->label('الوصف')
                ->default(true),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        abort_unless(auth()->user()->can('customer_groups.view'), 403);
        return view('livewire.customer-group.customer-group-manager');
    }
} 