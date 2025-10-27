<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\DTOs\Customer\CustomerData;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

/**
 * Customer Manager Component
 * 
 * This component provides functionality to manage customers.
 * Features:
 * - Customer list view
 * - Create new customer
 * - Edit customer
 * - Delete customer
 * - Customer detail view
 * - Customer filtering (group, type)
 * - Customer status management
 * 
 * @package App\Livewire\Customer
 */
final class CustomerManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var CustomerServiceInterface Customer service */
    private CustomerServiceInterface $customerService;

    /**
     * When the component is booted, the customer service is injected
     * 
     * @param CustomerServiceInterface $customerService Customer service
     * @return void
     */
    public function boot(CustomerServiceInterface $customerService): void 
    {
        $this->customerService = $customerService;
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
            ->query(Customer::query())
            ->emptyStateHeading('العميل لم يتم العثور عليه')
            ->emptyStateDescription(' أنشاء عميل جديد')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع العميل')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'عميل فردي / شخص',
                        'corporate' => 'شركة / مؤسّسة',
                    }),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('المجموعه'),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('الحاله')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_group_id')
                    ->label('مجموعه العميل')
                    ->options(CustomerGroup::where('status', true)->pluck('name', 'id'))
                    ->placeholder('جميع المجموعات')
                    ->native(false),
                SelectFilter::make('type')
                    ->label('نوع العميل')
                    ->options([
                        'individual' => 'عميل فردي / شخص',
                        'corporate' => 'شركة / مؤسّسة',
                    ])
                    ->placeholder('جميع المجموعات')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('معلومات العميل')
                    ->label('التفاصيل')
                    ->url(fn (Customer $record) => route('admin.customers.show', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->visible(fn () => auth()->user()->can('customers.detail')),
                Tables\Actions\EditAction::make()
                    ->modalHeading('تعديل العميل')
                    ->modalSubmitActionLabel('تحديث')
                    ->modalCancelActionLabel('إلغاء')
                    ->successNotificationTitle('تم تحديث العميل')
                    ->label('تعديل')
                    ->visible(fn () => auth()->user()->can('customers.edit'))
                    ->form($this->getCustomerForm())
                    ->using(function (Customer $record, array $data): Customer {
                        $customerData = CustomerData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerService->update($record, $customerData);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('حذف العميل')
                    ->modalDescription('هل انت متاكد من حذف العميل؟')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('تم حذف العميل بنجاح')
                    ->visible(fn () => auth()->user()->can('customers.delete'))
                    ->using(function (Customer $record): void {
                        $this->customerService->delete($record, true);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('أنشاء عميل')
                    ->modalHeading('جديد العميل')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalCancelActionLabel('الغاء')
                    ->createAnother(false)
                    ->successNotificationTitle('تم أنشاء العميل بنجاح')
                    ->visible(fn () => auth()->user()->can('customers.create'))
                    ->form($this->getCustomerForm())
                    ->using(function (array $data): Customer {
                        $customerData = CustomerData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerService->create($customerData);
                    }),
            ]);
    }

    /**
     * Creates the customer form
     * 
     * @return array Form components
     */
    protected function getCustomerForm(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('اسم العميل')
                        ->required()
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpan(1),
                    Forms\Components\Select::make('type')
                        ->label('نوع العميل')
                        ->options([
                            'corporate' => 'شركة / مؤسّسة',
                            'individual' => 'عميل فردي / شخص',
                        ])
                        ->default('corporate')
                        ->required()
                        ->reactive()
                        ->native(false)
                        ->columnSpan(1),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('tax_number')
                        ->label('رقم الهويه او الضريبه')
                        ->required(fn (callable $get) => $get('type') === 'corporate')
                        ->numeric()
                        ->rules([
                            fn (callable $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('type') === 'corporate' && strlen($value) !== 10) {
                                    $fail('يجب أن يتكون الرقم الضريبي او الهويه من 10 أرقام.');
                                } elseif ($get('type') === 'individual' && strlen($value) !== 11) {
                                    $fail('يجب أن يتكون الرقم الضريبي او الهويه من 11 أرقام.');
                                }
                            },
                        ])
                        ->placeholder(fn (callable $get) => 
                            $get('type') === 'corporate' ? '1234567890' : '12345678901'
                        )
                        ->helperText(fn (callable $get) => 
                            $get('type') === 'corporate' ? 
                                'يجب أن يتكون الرقم الضريبي او الهويه من 10 أرقام' : 
                                'يجب أن يتكون الرقم الضريبي او الهويه من 11 أرقام.'
                        )
                        ->columnSpan(fn (callable $get) => $get('type') === 'individual' ? 2 : 1),
                    Forms\Components\TextInput::make('tax_office')
                        ->label('مصلحة الضرائب')
                        ->required(fn (callable $get) => $get('type') === 'corporate')
                        ->visible(fn (callable $get) => $get('type') === 'corporate')
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpan(1),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->numeric()
                        ->minLength(10)
                        ->maxLength(11)
                        ->placeholder('1234567890'),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('city')
                        ->label('المحافظة / الولاية')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('district')
                        ->label('المدينة / المقاطعة / القسم')
                        ->maxLength(255),
                    Forms\Components\Select::make('customer_group_id')
                        ->label('مجموعة العميل')
                        ->options(CustomerGroup::where('status', true)->pluck('name', 'id'))
                        ->native(false)
                        ->placeholder('اختر مجموعة'),
                ])->columns(3),

            Forms\Components\Textarea::make('address')
                ->label('العنوان')
                ->rows(2)
                ->maxLength(1000),

            Forms\Components\Textarea::make('description')
                ->label('الوصف')
                ->rows(2)
                ->maxLength(1000),

            Forms\Components\Toggle::make('status')
                ->label('الحاله')
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
        return view('livewire.customer.customer-manager');
    }
} 