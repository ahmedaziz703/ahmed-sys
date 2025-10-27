<?php

namespace App\Livewire\Proposal;

use App\Models\ProposalTemplate;
use App\Models\ProposalItem;
use App\Models\Customer;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

/**
 * Proposal Template Form Component
 * 
 * This component provides a form for creating and editing proposal templates.
 * Features:
 * - Proposal template creation
 * - Proposal template editing
 * - Proposal item management
 * - Customer selection
 * - Validity date definition
 * - Payment terms definition
 * - Notes addition
 * 
 * @package App\Livewire\Proposal
 */
class ProposalTemplateForm extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var ProposalTemplate|null Edited proposal template */
    public ?ProposalTemplate $record = null;

    /** @var array Form data */
    public array $data = [];

    /** @var bool Editing mode status */
    public bool $isEdit = false;

    /**
     * Creates the form configuration
     * 
     * @param Forms\Form $form Form object
     * @return Forms\Form Configured form
     */
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->model($this->record ?? new ProposalTemplate())
            ->statePath('data');
    }

    /**
     * When the component is booted, it runs
     * 
     * @param int|null $id ID of the proposal template to be edited
     * @return void
     */
    public function mount(?int $id = null): void
    {
        $this->isEdit = (bool) $id;

        if ($this->isEdit) {
            $this->record = ProposalTemplate::with(['customer', 'items'])->findOrFail($id);
            $this->data = $this->record->toArray();
            
            // Add items data to the form
            $this->data['items'] = $this->record->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ];
            })->toArray();
        }

        $this->form->fill($this->data);
    }

    /**
     * Creates the form schema
     * 
     * @return array Form components
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('معلومات العرض')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('العميل')
                        ->options(Customer::query()->pluck('name', 'id'))
                        ->native(false)
                        ->searchable()
                        ->placeholder('العميل يرجى التحديد')
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label('Teklif Başlığı')
                        ->required()
                        ->maxLength(255)
                        ->default(fn () => 'Teklif ' . now()->format('d.m.Y')),
                    Forms\Components\RichEditor::make('content')
                        ->label('Teklif İçeriği')
                        ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Teklif Kalemleri')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Kalemler')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Kalem Adı')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('description')
                                ->label('الوصف')
                                ->rows(3)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('price')
                                ->label('Birim Fiyat')
                                ->required()
                                ->numeric()
                                ->prefix('$'),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Miktar')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->minValue(1),
                            Forms\Components\Select::make('unit')
                                ->label('Birim')
                                ->options([
                                    'piece' => 'Adet',
                                    'hour' => 'Saat',
                                    'day' => 'Gün',
                                    'month' => 'Ay',
                                    'year' => 'Yıl',
                                    'package' => 'Paket',
                                ])
                                ->required()
                                ->native(false)
                                ->default('piece'),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel('Kalem Ekle')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Teklif Detayları')
                ->schema([
                    Forms\Components\DatePicker::make('valid_until')
                        ->label('تاريخ الصلاحية')
                        ->required()
                        ->minDate(now())
                        ->default(now()->addDays(30)),
                    Forms\Components\Select::make('status')
                        ->label('الحاله')
                        ->options([
                            'draft' => 'Taslak',
                            'sent' => 'Gönderildi',
                            'accepted' => 'Kabul Edildi',
                            'rejected' => 'Reddedildi',
                            'expired' => 'Süresi Doldu',
                        ])
                        ->native(false)
                        ->default('draft')
                        ->required(),
                    Forms\Components\RichEditor::make('payment_terms')
                        ->label('شروط الدفع')
                        ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notlar')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    /**
     * Saves the form data
     * 
     * @throws \Exception Errors that can occur during the record
     * @return void
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Separate items data
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            \DB::beginTransaction();
            
            try {
                if ($this->isEdit) {
                    $this->record->update($data);
                    $proposal = $this->record;
                } else {
                    $proposal = ProposalTemplate::create($data);
                }

                // Save proposal items
                if (!empty($items)) {
                    // Delete existing items
                    if ($this->isEdit) {
                        $proposal->items()->delete();
                    }
                    
                    // Add new items
                    foreach ($items as $item) {
                        $proposal->items()->create([
                            'name' => $item['name'],
                            'description' => $item['description'] ?? null,
                            'price' => $item['price'],
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'],
                        ]);
                    }
                }

                \DB::commit();

                Notification::make()
                    ->success()
                    ->title($this->isEdit ? 'Teklif şablonu güncellendi' : 'Teklif şablonu أنشاءuldu')
                    ->duration(5000)
                    ->send();

                $this->redirectRoute('admin.proposals.templates');
                
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Hata!')
                ->body($e->getMessage())
                ->duration(5000)
                ->send();
        }
    }

    /**
     * Cancels the form operation
     * 
     * @return void
     */
    public function cancel(): void
    {
        $this->redirect(route('admin.proposals.templates'), navigate: true);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.proposal.form');
    }
}
