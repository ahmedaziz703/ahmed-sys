<x-table.table-layout
    :pageTitle="$project->name . ' - معاملات المشروع'"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'المشاريع', 'url' => route('admin.projects.index'), 'wire' => true],
        ['label' => $project->name]
    ]"
>
    <div class="h-full flex flex-col bg-gray-50">
        {{-- Header --}}
        <div class="p-4 bg-white border-b">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-medium text-gray-900">القوائم</h2>
                    <span class="text-sm text-gray-500">{{ $lists->count() }} قائمة</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-button.base wire:click="addList" class="gap-2">
                        <x-heroicon-m-plus class="w-5 h-5" />
                        إنشاء قائمة
                    </x-button.base>
                </div>
            </div>
        </div>

        {{-- Board --}}
        <div class="flex-1 overflow-hidden">
            <div class="h-full overflow-x-auto overflow-y-hidden custom-scrollbar">
                {{-- Lists Container --}}
                <div class="inline-flex h-full py-4 gap-4 lists-container">
                    @foreach($lists as $list)
                        <div class="w-[320px] flex-col bg-white rounded-lg border shadow-sm list-item"
                             data-list-id="{{ $list->id }}">
                            {{-- List Header --}}
                            <div class="p-3 flex justify-between items-center border-b list-handle cursor-move select-none">
                                <div class="flex items-center gap-4">
                                    <h3 class="font-medium text-gray-900">{{ $list->name }}</h3>
                                    <span class="text-xs text-gray-500">{{ $list->tasks->count() }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-button.icon
                                        icon="pencil-square"
                                        wire:click="editList({{ $list->id }})"
                                        class="hover:bg-gray-100"
                                        title="تعديل القائمة"
                                    />
                                    <x-button.icon 
                                        icon="plus"
                                        wire:click="addTask({{ $list->id }})"
                                        class="hover:bg-gray-100"
                                        title="إضافة مهمة"
                                    />
                                </div>
                            </div>
                            
                            <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar tasks-container"
                                 style="height: calc(100vh - 180px);">
                                @foreach($list->tasks->sortBy('order') as $task)
                                    <div class="task-item group bg-white p-3 rounded-lg border hover:shadow-md transition-all duration-200"
                                         data-task-id="{{ $task->id }}">
                                        {{-- Task Content --}}
                                        <div class="task-handle cursor-move select-none">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-gray-900 truncate">{{ $task->title }}</h4>
                                                    @if($task->content)
                                                        <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                                                            {{ Str::limit(strip_tags($task->content), 60) }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="ml-4 flex items-start gap-2">
                                                    <span @class([
                                                        'text-xs px-2 py-1 rounded-full font-medium',
                                                        'bg-red-100 text-red-700' => $task->priority === 'high',
                                                        'bg-yellow-100 text-yellow-700' => $task->priority === 'medium',
                                                        'bg-green-100 text-green-700' => $task->priority === 'low',
                                                    ])>
                                                        {{ match($task->priority) {
                                                            'low' => 'منخفض',
                                                            'medium' => 'متوسط',
                                                            'high' => 'عالي',
                                                        } }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between non-draggable">
                                            <div class="flex flex-wrap gap-1">
                                                @if($task->due_date)
                                                    <span class="text-xs text-gray-500 flex items-center gap-1 pointer-events-none">
                                                        <x-heroicon-m-calendar class="w-4 h-4" />
                                                        {{ $task->due_date->format('d.m.Y') }}
                                                    </span>
                                                @endif

                                                @foreach($task->labels as $label)
                                                    <span class="px-2 py-0.5 text-xs rounded-full pointer-events-none" 
                                                          style="background-color: {{ $label->color }}20; color: {{ $label->color }}">
                                                        {{ $label->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1 action-buttons">
                                                <x-button.icon 
                                                    icon="pencil-square"
                                                    wire:click="editTask({{ $task->id }})"
                                                    class="hover:bg-gray-100 z-10"
                                                    title="تعديل"
                                                />
                                                <x-button.icon 
                                                    icon="trash"
                                                    wire:click="confirmTaskDeletion({{ $task->id }})"
                                                    class="hover:bg-gray-100 hover:text-red-600 z-10"
                                                    title="حذف"
                                                />
                                            </div>
                                        </div>

                                        @if($task->assignedUser)
                                            <div class="mt-2 flex items-center gap-2">
                                                <img src="{{ $task->assignedUser->profile_photo_url }}" 
                                                     class="w-6 h-6 rounded-full" 
                                                     alt="{{ $task->assignedUser->name }}">
                                                <span class="text-sm text-gray-600">{{ $task->assignedUser->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                
                                {{-- Boş الحالهda gösterilecek alan --}}
                                @if($list->tasks->isEmpty())
                                    <div class="flex-1 flex items-center justify-center text-gray-400 border-2 border-dashed rounded-lg hover:bg-gray-50 transition-colors min-h-[300px] cursor-pointer"
                                         wire:click="addTask({{ $list->id }})"
                                         data-task-id="empty-{{ $list->id }}">
                                        <div class="text-center p-6">
                                            <x-heroicon-o-plus-circle class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                            <p class="text-base font-medium mb-1">اسحب المهمة هنا</p>
                                            <p class="text-sm text-gray-500">أو أنشئ مهمة جديدة</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Modals --}}
        @include('livewire.project.board.partials.list-modal')
        @include('livewire.project.board.partials.edit-list-modal')
        @include('livewire.project.board.partials.task-modal')
        
        {{-- نافذة الحذف --}}
        <x-modal wire:model="showDeleteModal" maxWidth="sm">
            <div class="p-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <x-heroicon-o-trash class="w-6 h-6 text-red-600" />
                    </div>
                </div>

                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    حذف المهمة
                </h3>

                <p class="text-sm text-gray-500 mb-6">
                    هل أنت متأكد من أنك تريد القيام بذلك؟
                </p>

                <div class="flex justify-center gap-3">
                    <x-button.base type="button" color="white" wire:click="$set('showDeleteModal', false)">
                        إلغاء
                    </x-button.base>
                    <x-button.base type="button" color="red" wire:click="deleteTask">
                        تأكيد
                    </x-button.base>
                </div>
            </div>
        </x-modal>
    </div>
</x-table.table-layout>

@push('scripts')
    @vite('resources/js/kanban.js')
    <script>
        document.addEventListener('livewire:navigated', () => {
            if (typeof initKanban === 'function') {
                initKanban();
            }
        });

        document.addEventListener('livewire:initialized', () => {
            if (typeof initKanban === 'function') {
                initKanban();
            }
        });

        // تشغيل في التحميل الأول أيضاً
        if (typeof initKanban === 'function') {
            initKanban();
        }
    </script>
@endpush 