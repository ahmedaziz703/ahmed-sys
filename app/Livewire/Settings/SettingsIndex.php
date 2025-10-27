<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Contracts\View\View;

/**
 * Settings Index Component
 * 
 * This component provides functionality to manage settings.
 * Features:
 * - Settings management
 */
final class SettingsIndex extends Component
{
    /** @var string Active tab */
    public string $activeTab = 'site'; // Varsayılan sekme

    /** @var array Tabs */
    public array $tabs = [];

    /**
     * When the component is mounted, the settings are loaded
     * 
     * @return void
     */
    public function mount()
    {
        $availableTabs = [
            'site' => ['name' => 'إعدادات الموقع', 'permission' => 'settings.site'],
            'notification' => ['name' => 'إعدادات الإخطار', 'permission' => 'settings.notification'],
            'telegram' => ['name' => 'إعدادات Telegram', 'permission' => 'settings.telegram'], // جديد sekme
        ];

        foreach ($availableTabs as $key => $tabInfo) {
            if (auth()->user()->can($tabInfo['permission'])) {
                $this->tabs[$key] = $tabInfo['name'];
            }
        }

        // If the default tab is not available, set the active tab to the first available one
        if (!empty($this->tabs) && !array_key_exists($this->activeTab, $this->tabs)) {
            $this->activeTab = array_key_first($this->tabs);
        }
        // If no tabs are available, handle appropriately (e.g., set activeTab to null or show an error)
        elseif (empty($this->tabs)) {
             $this->activeTab = ''; // Or handle as needed
        }
    }

    /**
     * Sets the active tab
     * 
     * @param string $tab Tab name
     * @return void
     */
    public function setActiveTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Gets the active component
     * 
     * @return string Active component name
     */
    public function getActiveComponent(): string
    {
        // Special case: use 'telegram-settings' component for 'telegram' tab
        if ($this->activeTab === 'telegram') {
            return 'settings.telegram-settings';
        }
        // For other tabs, use the standard format ('site' -> 'site-settings', 'notification' -> 'notification-settings')
        return 'settings.' . str_replace('_', '-', $this->activeTab) . '-settings';
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.settings.index');
    }
}