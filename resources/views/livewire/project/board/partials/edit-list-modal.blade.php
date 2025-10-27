<x-modal wire:model="showEditListModal">
    <x-slot name="title">
        تعديل القائمة
    </x-slot>

    <form wire:submit="updateList">
        <div class="space-y-4">
            <x-form-elements.input 
                wire:model="listData.name"
                label="اسم القائمة"
                required
                placeholder="اسم القائمة"
            />

            <div class="flex justify-end gap-3">
                <x-button.base type="button" color="white" wire:click="$set('showEditListModal', false)">
                    إلغاء
                </x-button.base>
                <x-button.base type="submit">
                    تحديث
                </x-button.base>
            </div>
        </div>
    </form>
</x-modal> 