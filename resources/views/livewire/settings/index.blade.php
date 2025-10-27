<x-table.table-layout
    pageTitle="إعدادات"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'إعدادات']
    ]"
>
    <div class="bg-white rounded-xl shadow-sm">
        {{-- Tab Headers --}}
        <div class="border-b border-gray-200 px-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                @foreach ($tabs as $tabKey => $tabLabel)
                    <button wire:click="setActiveTab('{{ $tabKey }}')"
                            :class="{ 'border-primary-500 text-primary-600': '{{ $activeTab }}' === '{{ $tabKey }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': '{{ $activeTab }}' !== '{{ $tabKey }}' }"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Load the appropriate Livewire component based on the active tab --}}
            @livewire($this->getActiveComponent(), key($activeTab))
        </div>
    </div>
</x-table.table-layout>