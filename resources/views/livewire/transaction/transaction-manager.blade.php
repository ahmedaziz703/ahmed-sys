<x-table.table-layout
    pageTitle="المعاملات"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'المعاملات']
    ]"
>
    <div class="mb-6">
        <livewire:transaction.widgets.transaction-stats-widget />
    </div>

    {{-- Transaction Filters --}}
    <div class="mb-6">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl">
                <div class="flex flex-wrap items-center justify-center py-1.5">
                    @php
                        $filterOptions = [
                            'all' => [
                                'label' => 'جميع المعاملات',
                                'icon' => 'heroicon-o-squares-2x2'
                            ],
                            'income' => [
                                'label' => 'الإيرادات',
                                'icon' => 'heroicon-o-arrow-trending-up'
                            ],
                            'expense' => [
                                'label' => 'المصروفات',
                                'icon' => 'heroicon-o-arrow-trending-down'
                            ],
                            'transfer' => [
                                'label' => 'التحويلات',
                                'icon' => 'heroicon-o-arrows-right-left'
                            ],
                            'payments' => [
                                'label' => 'المدفوعات',
                                'icon' => 'heroicon-o-credit-card'
                            ],
                            'atm' => [
                                'label' => 'معاملات الصراف الآلي',
                                'icon' => 'heroicon-o-building-office'
                            ],
                        ];
                    @endphp

                    @foreach ($filterOptions as $key => $option)
                        <button 
                            type="button" 
                            wire:click="setFilter('{{ $key }}')"
                            @class([
                                'relative flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
                                // الحالة النشطة
                                $activeFilter === $key 
                                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            ])
                        >
                            @if(isset($option['icon']))
                                <x-icon 
                                    name="{{ $option['icon'] }}" 
                                    class="w-4 h-4" 
                                />
                            @endif
                            
                            {{ $option['label'] }}

                            @if (isset($filterCounts[$key]))
                                <span @class([
                                    'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-medium transition-colors',
                                    // الحالة النشطة
                                    $activeFilter === $key 
                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                ])>
                                    {{ $filterCounts[$key] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    {{-- / Transaction Filters --}}

    {{ $this->table }}
</x-table.table-layout> 