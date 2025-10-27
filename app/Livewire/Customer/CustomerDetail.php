<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\CustomerAgreement;
use App\Models\CustomerCredential;
use App\Models\CustomerNote;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\DTOs\Customer\NoteData;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Exceptions\Halt;
use App\Models\User;

/**
 * Customer Detail Component
 * 
 * This component provides functionality to view and manage customer details and customer notes.
 * Features:
 * - Customer information view
 * - Customer notes creation
 * - Note history view
 * - Note type management (note, call, meeting, email, other)
 * - Customer information management (sensitive information)
 * - Agreement management (recurring payments)
 * 
 * @package App\Livewire\Customer
 */
final class CustomerDetail extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var Customer Customer model */
    public Customer $customer;

    /** @var bool Note creation modal visibility */
    public $showNoteModal = false;

    /** @var bool Information creation modal visibility */
    public $showCredentialModal = false;

    /** @var bool Agreement creation modal visibility */
    public $showAgreementModal = false;

    /** @var array Note data */
    public $data = [];

    /** @var array Information data */
    public $credentialData = [
        'value' => []
    ];

    /** @var array Agreement data */
    public $agreementData = [];

    /** @var CustomerAgreement|null Editing agreement */
    public ?CustomerAgreement $editingAgreement = null;

    /** @var CustomerCredential|null Editing sensitive information */
    public ?CustomerCredential $editingCredential = null;

    /** @var CustomerNote|null Editing note */
    public ?CustomerNote $editingNote = null;

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
     * When the component is mounted, the customer data is set
     * 
     * @param Customer $customer Customer model
     * @return void
     */
    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
        $this->form->fill([
            'data' => [
                'type' => 'note',
                'content' => '',
                'activity_date' => now(),
                'assigned_user_id' => null,
            ],
        ]);
    }

    /**
     * Opens the note creation modal
     * 
     * @return void
     */
    public function addNote(): void
    {
        $this->editingNote = null;
        $this->data = [
            'type' => 'note',
            'content' => '',
            'activity_date' => now()->format('Y-m-d\TH:i'),
            'assigned_user_id' => null,
        ];
        $this->showNoteModal = true;
    }

    /**
     * Opens the information creation modal
     * 
     * @return void
     */
    public function addCredential(): void
    {
        $this->editingCredential = null;
        $this->credentialData = ['value' => []];
        $this->showCredentialModal = true;
    }

    /**
     * Opens the agreement creation modal
     * 
     * @return void
     */
    public function addAgreement(): void
    {
        $this->editingAgreement = null;
        $this->agreementData = [
            'name' => '',
            'description' => '',
            'amount' => '',
            'start_date' => '',
            'next_payment_date' => '',
        ];
        $this->showAgreementModal = true;
    }

    /**
     * Adds a sensitive information value
     * 
     * @return void
     */
    public function addCredentialValue(): void
    {
        $this->credentialData['value'][] = '';
    }

    /**
     * Removes a sensitive information value
     * 
     * @param int $index Index of the value to be removed
     * @return void
     */
    public function removeCredentialValue(int $index): void
    {
        unset($this->credentialData['value'][$index]);
        $this->credentialData['value'] = array_values($this->credentialData['value']);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.customer.customer-detail', [
            'notes' => $this->customer->notes()
                ->with(['user', 'assignedUser'])
                ->latest('activity_date')
                ->get(),
            'credentials' => $this->customer->credentials()
                ->with('user')
                ->latest()
                ->get(),
            'agreements' => $this->customer->agreements()
                ->with('user')
                ->latest()
                ->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Creates the form schema
     * 
     * @param Form $form Form object
     * @return Form Form schema
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('data.type')
                    ->label('Not Türü')
                    ->options([
                        'note' => 'Not',
                        'call' => 'رقم الهاتف Görüşmesi',
                        'meeting' => 'Toplantı',
                        'email' => 'البريد الإلكتروني',
                        'other' => 'Diğer',
                    ])
                    ->required(),
                DateTimePicker::make('data.activity_date')
                    ->label('تاريخ النشاط')
                    ->required(),
                Select::make('data.assigned_user_id')
                    ->label('الشخص المعين')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Textarea::make('data.content')
                    ->label('İçerik')
                    ->required()
                    ->minLength(3)
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    /**
     * Saves the note
     * 
     * @return void
     */

    public function saveNote(): void
    {
        $this->validate([
            'data.type' => ['required', 'in:note,call,meeting,email,other'],
            'data.content' => ['required', 'string', 'min:3', 'max:1000'],
            'data.activity_date' => ['required', 'date'],
            'data.assigned_user_id' => ['nullable', 'exists:users,id'],
        ], [
            'data.type.required' => 'Not türü seçilmelidir.',
            'data.type.in' => 'Geçersiz not türü seçildi.',
            'data.content.required' => 'Not içeriği boş bırakılamaz.',
            'data.content.min' => 'Not içeriği en az 3 karakter olmalıdır.',
            'data.content.max' => 'Not içeriği en fazla 1000 karakter olabilir.',
            'data.activity_date.required' => 'Aktivite تاريخi seçilmelidir.',
            'data.activity_date.date' => 'Geçerli bir تاريخ seçilmelidir.',
            'data.assigned_user_id.exists' => 'Seçilen kullanıcı لم يتم العثور عليه.',
        ]);

        try {
            $noteData = NoteData::fromArray([
                'type' => $this->data['type'],
                'content' => $this->data['content'],
                'activity_date' => $this->data['activity_date'],
                'user_id' => auth()->id(),
                'customer_id' => $this->customer->id,
                'assigned_user_id' => $this->data['assigned_user_id'] ?? null,
            ]);

            if ($this->editingNote) {
                $this->editingNote->update($noteData->toArray());
                $message = 'Not güncellendi';
            } else {
                $this->customerService->addNote($this->customer, $noteData);
                $message = 'Not eklendi';
            }

            $this->showNoteModal = false;
            $this->data = [];
            $this->editingNote = null;

            Notification::make()
                ->title($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata oluştu')
                ->body('Not kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Saves the sensitive information
     * 
     * @return void
     */
    public function saveCredential(): void
    {
        $this->validate([
            'credentialData.name' => ['required', 'string'],
            'credentialData.value' => ['required', 'array'],
            'credentialData.value.*' => ['required', 'string'],
        ], [
            'credentialData.name.required' => 'Bilgi adı boş bırakılamaz.',
            'credentialData.value.required' => 'En az bir değer girilmelidir.',
            'credentialData.value.*.required' => 'Tüm değerler doldurulmalıdır.',
        ]);

        try {
            $data = [
                'user_id' => auth()->id(),
                'customer_id' => $this->customer->id,
                'name' => $this->credentialData['name'],
                'value' => array_values(array_filter($this->credentialData['value'])),
                'status' => true,
            ];

            if ($this->editingCredential) {
                $this->editingCredential->update($data);
                $message = 'Bilgi güncellendi';
            } else {
                CustomerCredential::create($data);
                $message = 'Bilgi eklendi';
            }

            $this->showCredentialModal = false;
            $this->credentialData = ['value' => []];
            $this->editingCredential = null;

            Notification::make()
                ->title($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Bilgi kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Creates a new agreement
     * 
     * @return void
     */
    public function saveAgreement(): void
    {
        $this->validate([
            'agreementData.name' => ['required', 'string'],
            'agreementData.description' => ['nullable', 'string'],
            'agreementData.amount' => ['required', 'numeric', 'min:0'],
            'agreementData.start_date' => ['required', 'date'],
            'agreementData.next_payment_date' => ['required', 'date', 'after:agreementData.start_date'],
        ], [
            'agreementData.name.required' => 'اسم الاتفاقية لا يمكن أن يكون فارغاً.',
            'agreementData.amount.required' => 'المبلغ لا يمكن أن يكون فارغاً.',
            'agreementData.amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'agreementData.amount.min' => 'المبلغ لا يمكن أن يكون أقل من 0.',
            'agreementData.start_date.required' => 'يجب اختيار تاريخ البداية.',
            'agreementData.start_date.date' => 'يجب اختيار تاريخ بداية صالح.',
            'agreementData.next_payment_date.required' => 'يجب اختيار تاريخ الدفعة التالية.',
            'agreementData.next_payment_date.date' => 'يجب اختيار تاريخ دفع تالي صالح.',
            'agreementData.next_payment_date.after' => 'تاريخ الدفعة التالية يجب أن يكون بعد تاريخ البداية.',
        ]);

        try {
            $data = [
                'user_id' => auth()->id(),
                'customer_id' => $this->customer->id,
                'name' => $this->agreementData['name'],
                'description' => $this->agreementData['description'] ?? null,
                'amount' => $this->agreementData['amount'],
                'start_date' => $this->agreementData['start_date'],
                'next_payment_date' => $this->agreementData['next_payment_date'],
                'status' => $this->agreementData['status'] ?? 'active',
            ];

            if ($this->editingAgreement) {
                $this->editingAgreement->update($data);
            } else {
                CustomerAgreement::create($data);
            }

            $this->showAgreementModal = false;
            $this->agreementData = [];
            $this->editingAgreement = null;

            Notification::make()
                ->title($this->editingAgreement ? 'Anlaşma güncellendi' : 'Anlaşma eklendi')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Anlaşma kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Deletes the agreement
     * 
     * @param int $id ID of the agreement to be deleted
     * @return void
     */
    public function deleteAgreement(int $id): void
    {
        try {
            $agreement = CustomerAgreement::findOrFail($id);
            $agreement->delete();

            Notification::make()
                ->title('Anlaşma silindi')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Anlaşma silinirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Deletes the sensitive information
     * 
     * @param int $id ID of the sensitive information to be deleted
     * @return void
     */
    public function deleteCredential(int $id): void
    {
        try {
            $credential = CustomerCredential::findOrFail($id);
            $credential->delete();

            Notification::make()
                ->title('Bilgi silindi')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Bilgi silinirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Opens the agreement editing modal
     * 
     * @param int $id ID of the agreement to be edited
     * @return void
     */
    public function editAgreement(int $id): void
    {
        $this->editingAgreement = CustomerAgreement::findOrFail($id);
        $this->agreementData = [
            'name' => $this->editingAgreement->name,
            'description' => $this->editingAgreement->description,
            'amount' => $this->editingAgreement->amount,
            'start_date' => $this->editingAgreement->start_date->format('Y-m-d'),
            'next_payment_date' => $this->editingAgreement->next_payment_date->format('Y-m-d'),
            'status' => $this->editingAgreement->status,
        ];
        $this->showAgreementModal = true;
    }

    /**
     * Opens the sensitive information editing modal
     * 
     * @param int $id ID of the sensitive information to be edited
     * @return void
     */
    public function editCredential(int $id): void
    {
        $this->editingCredential = CustomerCredential::findOrFail($id);
        $this->credentialData = [
            'name' => $this->editingCredential->name,
            'value' => is_array($this->editingCredential->value) ? $this->editingCredential->value : [$this->editingCredential->value],
        ];
        $this->showCredentialModal = true;
    }

    /**
     * Opens the note editing modal
     * 
     * @param int $id ID of the note to be edited
     * @return void
     */
    public function editNote(int $id): void
    {
        $this->editingNote = CustomerNote::findOrFail($id);
        $this->data = [
            'type' => $this->editingNote->type,
            'content' => $this->editingNote->content,
            'activity_date' => $this->editingNote->activity_date->format('Y-m-d\TH:i'),
            'assigned_user_id' => $this->editingNote->assigned_user_id,
        ];
        $this->showNoteModal = true;
    }
} 