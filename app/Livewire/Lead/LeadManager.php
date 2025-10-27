<?php

namespace App\Livewire\Lead;

use App\Models\Lead;
use App\Models\User;
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
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Services\Lead\Contracts\LeadServiceInterface;
use App\DTOs\Lead\LeadData;

/**
 * Lead Manager Component
 * 
 * This component provides functionality to manage leads.
 * Features:
 * - Lead list view
 * - New lead creation
 * - Lead editing
 * - Lead deletion
 * - Lead conversion to customer
 * - Status tracking
 * - Source and status filtering
 * 
 * @package App\Livewire\Lead
 */
final class LeadManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var LeadServiceInterface Lead service */
    private LeadServiceInterface $leadService;

    /**
     * When the component is booted, the lead service is injected
     * 
     * @param LeadServiceInterface $leadService Lead service
     * @return void
     */
    public function boot(LeadServiceInterface $leadService): void 
    {
        $this->leadService = $leadService;
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
            ->query(Lead::query())
            ->defaultGroup(
                Tables\Grouping\Group::make('status')
                    ->label('الحاله')
                    ->getTitleFromRecordUsing(fn (Lead $record): string => match ($record->status) {
                        'new' => 'جديد',
                        'contacted' => 'İletişime Geçildi',
                        'proposal_sent' => 'Teklif Gönderildi',
                        'negotiating' => 'Görüşülüyor',
                        'converted' => 'العميلye Çevrildi',
                        'lost' => 'Kaybedildi',
                        default => ucfirst($record->status),
                    })
            )
            ->emptyStateHeading(' العميل لم يتم العثور عليه')
            ->emptyStateDescription(' أنشاء عميل محتمل')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_contact_date')
                    ->label('تاريخ المقابلة التالية')
                    ->dateTime('d/m/Y h:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحاله')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'جديد',
                        'contacted' => 'İletişime Geçildi',
                        'proposal_sent' => 'Teklif Gönderildi',
                        'negotiating' => 'Görüşülüyor',
                        'converted' => 'العميلye Çevrildi',
                        'lost' => 'Kaybedildi',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'contacted' => 'info',
                        'proposal_sent' => 'warning',
                        'negotiating' => 'primary',
                        'converted' => 'success',
                        'lost' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label('Kaynak')
                    ->options([
                        'website' => 'Web Sitesi',
                        'referral' => 'Referans',
                        'social_media' => 'Sosyal Medya',
                        'other' => 'Diğer',
                    ])
                    ->native(false)
                    ->placeholder('Tüm Kaynaklar'),
                SelectFilter::make('status')
                    ->label('الحاله')
                    ->options([
                        'new' => 'جديد',
                        'contacted' => 'İletişime Geçildi',
                        'proposal_sent' => 'Teklif Gönderildi',
                        'negotiating' => 'Görüşülüyor',
                        'converted' => 'العميلye Çevrildi',
                        'lost' => 'Kaybedildi',
                    ])
                    ->native(false)
                    ->placeholder('Tüm الحالهlar'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->visible(fn () => auth()->user()->can('leads.edit'))
                    ->hidden(fn (Lead $record): bool => $record->status === 'converted')
                    ->modalHeading('محتمل العميلyi تعديل')
                    ->modalSubmitActionLabel('تحديث')
                    ->modalCancelActionLabel('الغاء')
                    ->form($this->getLeadForm())
                    ->successNotificationTitle('تعديل العميل'),
                Tables\Actions\Action::make('convert')
                    ->label('تحويل العميل')
                    ->visible(fn () => auth()->user()->can('leads.convert_customer'))
                    ->hidden(fn (Lead $record): bool => $record->status === 'converted')
                    ->icon('heroicon-m-user-plus')
                    ->modalWidth('4xl')
                    ->color('success')
                    ->form([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('نوع العميل')
                                    ->options([
                                        'corporate' => 'شركة / مؤسّسة',
                                        'individual' => 'عميل فردي / شخص',
                                    ])
                                    ->default(fn (Lead $record): string => $record->type)
                                    ->required()
                                    ->reactive()
                                    ->native(false)
                                    ->columnSpan(12),
                            ])->columns(12),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('tax_number')
                                    ->label(fn (callable $get) => $get('type') === 'corporate' ? 'Vergi No' : 'رقم الهاتف')
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
                                    ->columnSpan(fn (callable $get) => $get('type') === 'individual' ? 12 : 6),

                                Forms\Components\TextInput::make('tax_office')
                                    ->label('مصلحة الضرائب')
                                    ->required(fn (callable $get) => $get('type') === 'corporate')
                                    ->visible(fn (callable $get) => $get('type') === 'corporate')
                                    ->columnSpan(6),
                            ])->columns(12),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('customer_group_id')
                                    ->label('مجموعه العميل')
                                    ->options(CustomerGroup::where('status', true)->pluck('name', 'id'))
                                    ->native(false)
                                    ->columnSpan(12),
                            ])->columns(12),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Textarea::make('conversion_reason')
                                    ->label('سبب التحويل')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpan(12),
                            ])->columns(12),
                    ])
                    ->action(function (array $data, Lead $record): void {
                        $this->leadService->convertToCustomer($record, $data);
                    })
                    ->modalHeading('تحويل العميل')
                    ->modalSubmitActionLabel('تحديث'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => auth()->user()->can('leads.delete'))
                    ->modalHeading('محتمل العميلyi Sil')
                    ->modalDescription('Bu محتمل العميلyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('محتمل العميل silindi'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(' أنشاء عميل محتمل')
                    ->visible(fn () => auth()->user()->can('leads.create'))
                    ->modalHeading(' أنشاء عميل محتمل')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalCancelActionLabel('الغاء')
                    ->createAnother(false)
                    ->successNotificationTitle(' أنشاء عميل محتمل')
                    ->using(function (array $data): Lead {
                        $leadData = LeadData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                            'status' => 'new',
                        ]);
                        return $this->leadService->create($leadData);
                    })
                    ->form($this->getLeadForm()),
            ]);
    }

    /**
     * Creates the lead form
     * 
     * @return array Form components
     */
    protected function getLeadForm(): array
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
                        ->native(false)
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
                        ->placeholder('05555555555'),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('city')
                        ->label('المحافظة / الولاية')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('district')
                        ->label('المدينة / المقاطعة / القسم')
                        ->maxLength(255),
                    Forms\Components\Select::make('source')
                        ->label('المصدر')
                        ->options([
                            'website' => 'موقع إلكتروني',
                            'referral' => 'إحالة / توصية',
                            'social_media' => 'وسائل التواصل الاجتماعي',
                            'other' => 'أخرى',
                        ])
                        ->default('other')
                        ->required()
                        ->native(false),
                ])->columns(3),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'new' => 'جديد',
                            'contacted' => 'تم التواصل',
                            'proposal_sent' => 'تم إرسال عرض',
                            'negotiating' => 'قيد التفاوض',
                            'lost' => 'مفقود / خسارة',
                        ])
                        ->default('new')
                        ->required()
                        ->native(false),
                    Forms\Components\DateTimePicker::make('next_contact_date')
                        ->label('تاريخ التواصل التالي')
                        ->seconds(false)
                        ->native(false),
                ])->columns(2),

            Forms\Components\Textarea::make('address')
                ->label('العنوان')
                ->rows(2)
                ->maxLength(1000),

            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(2)
                ->maxLength(1000),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        abort_unless(auth()->user()->can('leads.view'), 403);
        return view('livewire.lead.lead-manager');
    }
} 