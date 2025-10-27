<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Features\SupportFileUploads\WithFileUploads; 

/**
 * Site Settings Component
 * 
 * This component provides functionality to manage site settings.
 * Features:
 * - Site settings management
 */
final class SiteSettings extends Component implements Forms\Contracts\HasForms
{
    /** @var array Form data */
    use Forms\Concerns\InteractsWithForms;
    use WithFileUploads;

    /** @var array Form data */
    public ?array $data = [];

    /**
     * When the component is mounted, the settings are loaded
     * 
     * @return void
     */
    public function mount(): void
    {
        $settings = Setting::where('group', 'site')
                           ->pluck('value', 'key')
                           ->toArray();
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
                Forms\Components\Section::make('إعدادات الموقع')
                    ->schema([
                        Forms\Components\TextInput::make('data.site_title') 
                            ->label('عنوان الموقع')
                            ->required(),
                        Forms\Components\FileUpload::make('data.site_logo')
                            ->label('الشعار')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'])
                            ->directory('site')
                            ->imageEditor() 
                            ->nullable(), 
                        Forms\Components\FileUpload::make('data.site_favicon')
                            ->label('الأيقونة المفضلة')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'])
                            ->directory('site')
                            ->imageResizeTargetWidth('32')
                            ->imageResizeTargetHeight('32')
                            ->nullable(),
                    ])
                    ->columns(1), 
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

            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'site'],
                [
                    'value' => $value,
                    'type' => is_bool($value) ? 'boolean' : (is_array($value) ? 'json' : 'text'),
                    'is_translatable' => false
                ]
            );
        }

        Notification::make()
            ->title('تم حفظ إعدادات الموقع بنجاح')
            ->success()
            ->send();

        // Clear cache as settings have changed
        \Illuminate\Support\Facades\Cache::forget('site_settings');

        // Warm cache immediately with newly saved settings
        \Illuminate\Support\Facades\Cache::forever('site_settings', $data);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        // Return a simple view that includes the form and save button
        return view('livewire.settings.generic-settings-view');
    }
}