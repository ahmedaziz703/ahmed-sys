<div>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end"> 
            <x-filament::button type="submit">
                حفظ الإعدادات
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>