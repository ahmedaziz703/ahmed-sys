<x-modal 
    wire:model="showListModal"
    x-on:close="$wire.closeListModal()"
>
    <x-slot name="title">
        إنشاء قائمة
    </x-slot>

    <form wire:submit="createList">
        <div class="space-y-4">
            <x-form-elements.input 
                wire:model="listData.name"
                label="اسم القائمة"
                required
                placeholder="مثال: المهام، قيد التنفيذ، مكتملة"
            />

            <div class="flex justify-end gap-3">
                <x-button.base type="button" color="white" wire:click="closeListModal">
                    إلغاء
                </x-button.base>
                <x-button.base type="submit">
                    حفظ
                </x-button.base>
            </div>
        </div>
    </form>
</x-modal> 