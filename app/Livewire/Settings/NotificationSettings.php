<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;

/**
 * Notification Settings Component
 * 
 * This component provides functionality to manage notification settings.
 * Features:
 * - Notification settings management
 */
final class NotificationSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    /** @var array Form data */
    public ?array $data = [];

    /**
     * When the component is mounted, the settings are loaded
     * 
     * @return void
     */
    public function mount(): void
    {
        $settings = Setting::where('group', 'notification')->pluck('value', 'key')->toArray();
        $booleanKeys = [
            'notify_credit_card_statement',
            'notify_loan_payment',
            'notify_recurring_payment',
            'notify_debt_receivable',
        ];
        foreach ($booleanKeys as $key) {
            if (!isset($settings[$key])) {
                $settings[$key] = false;
            } else {
                $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }
        $this->form->fill(['data' => $settings]);
    }

    /**
     * Creates the form configuration
     * 
     * @param Forms\Form $form Form object
     * @return Forms\Form Configured form
     */
    public function form(Form $form): Form
    {
        return $form   
            ->schema([
                Forms\Components\Section::make('إعدادات الإخطار')
                    ->description('يتم إرسال إشعارات عبر Telegram')
                    ->schema([
                        Forms\Components\Select::make('data.notify_credit_card_statement')
                            ->label('إشعار بطاقة ائتمان')
                            ->helperText('يتم إرسال إشعارات لبطاقات الائتمان القادمة')
                            ->options([
                                true => 'مفعل',
                                false => 'معطل',
                            ])
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('data.notify_loan_payment')
                            ->label('إشعار دفع القرض')
                            ->helperText('يتم إرسال إشعارات لدفعات القروض القادمة')
                            ->options([
                                true => 'مفعل',
                                false => 'معطل',
                            ])
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('data.notify_recurring_payment')
                            ->label('إشعار الدفع المتكرر')
                            ->helperText('يتم إرسال إشعارات للمعاملات/الاشتراكات المتكررة')
                            ->options([
                                true => 'مفعل',
                                false => 'معطل',
                            ])
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('data.notify_debt_receivable')
                            ->label('كشف حساب المدين والائتمان')
                            ->helperText('يتم إرسال إشعارات للديون والقروض القادمة')
                            ->options([
                                true => 'مفعل',
                                false => 'معطل',
                            ])
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * Saves the form data
     * 
     * @return void
     */
    public function save(): void
    {
        $data = $this->form->getState()['data'];

        foreach ($data as $key => $value) {
            // Values from select may be 'true'/'false' strings, convert to boolean.
            $processedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'notification'],
                [
                    'value' => $processedValue,
                    'type' => 'boolean',
                    'is_translatable' => false
                ]
            );
        }

        Notification::make()
            ->title('تم حفظ إعدادات الإشعارات بنجاح')
            ->success()
            ->send();
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.settings.generic-settings-view');
    }
}