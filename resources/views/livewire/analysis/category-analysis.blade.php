<div>
<x-table.table-layout
    pageTitle="تحليل الفئات"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'تحليل الفئات']
    ]"
>
    <!-- Error message field -->
    @if($errorMessage)
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-red-700 font-medium">{{ $errorMessage }}</span>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <x-filament::section heading="المرشحات" class="mb-6">
        <div wire:key="filters">
            {{ $this->form }}
        </div>
    </x-filament::section>

    <!-- Stats Overview Widget -->
    <div class="mt-8 mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Amount -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-blue-600">
                <h3 class="text-sm font-medium text-white">إجمالي المبلغ</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold text-blue-600">
                    {{ number_format($totalAmount, 2, ',', '.') }} ر.ي
                </p>
                <p class="text-sm mt-1 text-gray-600">
                    في الفترة المحددة
                </p>
            </div>
        </div>

        <!-- Average Amount -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-green-600">
                <h3 class="text-sm font-medium text-white">متوسط المبلغ</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold text-green-600">
                    {{ number_format($averageAmount, 2, ',', '.') }} ر.ي
                </p>
                <p class="text-sm mt-1 text-gray-600">
                    متوسط لكل معاملة
                </p>
            </div>
        </div>

        <!-- Category Count -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-purple-600">
                <h3 class="text-sm font-medium text-white">عدد الفئات</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold text-purple-600">
                    {{ count($categoryData) }}
                </p>
                <p class="text-sm mt-1 text-gray-600">
                    فئة نشطة
                </p>
            </div>
        </div>

        <!-- Transaction Count -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-orange-500">
                <h3 class="text-sm font-medium text-white">إجمالي المعاملات</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold text-orange-500">
                    {{ $categoryData->sum('transaction_count') }}
                </p>
                <p class="text-sm mt-1 text-gray-600">
                    في جميع الفئات
                </p>
            </div>
        </div>
    </div>

    <!-- Category Analysis -->
    <x-filament::section 
        heading="{{ $analysisType === 'income' ? 'الإيرادات' : 'المصروفات' }}" 
        description="تحليل مفصل ومقارنة الفئات"
    >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($categoryData as $category)
                <div class="bg-white rounded-lg border border-gray-200 hover:border-primary-500 transition-colors duration-200">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-base font-medium text-gray-900">{{ $category->name }}</h4>
                            @if(isset($categoryGrowth[$category->id]))
                                @if($categoryGrowth[$category->id]['trend'] === 'up')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                        {{ $categoryGrowth[$category->id]['percentage'] }}%
                                    </span>
                                @elseif($categoryGrowth[$category->id]['trend'] === 'down')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/>
                                        </svg>
                                        {{ $categoryGrowth[$category->id]['percentage'] }}%
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                        {{ $categoryGrowth[$category->id]['percentage'] }}%
                                    </span>
                                @endif
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-500">{{ number_format($category->total_amount, 2, ',', '.') }} $</span>
                            <span class="text-xs text-gray-400">{{ $category->transaction_count }} معاملة</span>
                        </div>

                        <div class="relative">
                            <div class="overflow-hidden h-1.5 flex rounded bg-gray-100">
                                <div style="width: {{ ($category->total_amount / $totalAmount) * 100 }}%" 
                                     class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $analysisType === 'income' ? 'bg-green-500' : 'bg-red-500' }}">
                                </div>
                            </div>
                            <div class="mt-1 flex justify-between items-center">
                                <span class="text-xs {{ $analysisType === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                    %{{ number_format(($category->total_amount / $totalAmount) * 100, 1) }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    متوسط {{ number_format($category->average_amount, 0, ',', '.') }} ل.ل
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-table.table-layout>
</div> 