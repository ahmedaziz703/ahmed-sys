<x-modal wire:model="showTaskModal" maxWidth="2xl">
    <form wire:submit="createTask" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
            {{ $editingTask ? 'تعديل المهمة' : 'مهمة جديدة' }}
        </h2>

        <div class="space-y-4">
            {{-- Title and Short Description --}}
            <div class="grid grid-cols-1 gap-4">
                <x-form-elements.input 
                    wire:model="taskData.title" 
                    label="العنوان"
                    required
                />

        
            </div>

            {{-- Content Editor --}}
            <x-form-elements.rich-editor
                wire:model="taskData.content"
                label="الوصف التفصيلي"
            />

            {{-- Priority and Due Date --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form-elements.select
                    wire:model="taskData.priority"
                    label="الاولويه"
                    :options="[
                        'low' => 'قليل',
                        'medium' => 'وسط',
                        'high' => 'عالي'
                    ]"
                />

                <x-form-elements.date
                    wire:model="taskData.due_date"
                    label="تاريخ البدء"
                />
            </div>

            {{-- Assigned Person --}}
            <x-form-elements.select
                wire:model="taskData.assigned_to"
                label="الشخص المعين"
                :options="$users->pluck('name', 'id')->prepend('يرجى التحديد...', '')"
            />

            {{-- Checklist Items --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">قائمه المهام</label>
                <div class="mt-2 space-y-2">
                    @foreach($taskData['checklist'] ?? [] as $index => $item)
                        <div class="col-span-12 flex items-center gap-2">
                            <div class="flex-none">
                                <input type="checkbox" 
                                       wire:model="taskData.checklist.{{ $index }}.completed"
                                       class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            </div>
                            <div class="flex-1">
                                <x-form-elements.input 
                                    wire:model="taskData.checklist.{{ $index }}.text"
                                    placeholder="المهمة..."
                                />
                            </div>
                            <div class="flex-none">
                                <button type="button" 
                                        wire:click="removeChecklistItem({{ $index }})"
                                        class="text-red-600 hover:text-red-800">
                                    <x-heroicon-m-trash class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                    
                    <button type="button" 
                            wire:click="addChecklistItem"
                            class="text-sm text-primary-600 hover:text-primary-800">
                        <div class="flex items-center gap-1">
                            <x-heroicon-m-plus class="w-4 h-4" />
                            <span>إضافة عنصر</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-button.base type="button" color="white" wire:click="closeTaskModal">
                    إلغاء
            </x-button.base>
            <x-form-elements.button 
                type="submit">
                حفظ
            </x-form-elements.button>
        </div>
    </form>
</x-modal> 