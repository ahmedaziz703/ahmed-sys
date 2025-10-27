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
 * Telegram Settings Component
 * 
 * This component provides functionality to manage telegram settings.
 * Features:
 * - Telegram settings management
 */
final class TelegramSettings extends Component implements Forms\Contracts\HasForms
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
        $settings = Setting::where('group', 'telegram')
                           ->pluck('value', 'key')
                           ->toArray();

        // Set default if not present in database
        if (!isset($settings['telegram_enabled'])) {
             $settings['telegram_enabled'] = false; // Default to 'No'
        } else {
             $settings['telegram_enabled'] = filter_var($settings['telegram_enabled'], FILTER_VALIDATE_BOOLEAN);
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
                Forms\Components\Section::make('إعدادات Telegram')
                    ->schema([
                        Forms\Components\Select::make('data.telegram_enabled')
                            ->label('تفعيل الإشعارات عبر Telegram')
                            ->options([
                                true => 'مفعل',
                                false => 'معطل',
                            ])
                            ->native(false)
                            ->required()
                            ->live()
                            ->default(false),
                        Forms\Components\TextInput::make('data.telegram_bot_token')
                            ->label('رمز البوت')
                            ->required(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled'))
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled')),
                        Forms\Components\TextInput::make('data.telegram_chat_id')
                            ->label('رقم الدردشة') // Label is already near Turkish usage
                            ->required(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled'))
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled')),
                    ])->columns(1), // Tek sütunlu görünüm
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

        // Convert 'telegram_enabled' from select to boolean
        if (isset($data['telegram_enabled'])) {
            $data['telegram_enabled'] = filter_var($data['telegram_enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'telegram'],
                [
                    'value' => $value,
                    'type' => is_bool($value) ? 'boolean' : 'text',
                    'is_translatable' => false
                ]
            );
        }

        Notification::make()
            ->title('تم حفظ إعدادات Telegram بنجاح')
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