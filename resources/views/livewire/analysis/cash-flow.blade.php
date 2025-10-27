<div>
<x-table.table-layout
    pageTitle="تحليل التدفق النقدي"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'تحليل التدفق النقدي']
    ]"
>
    <!-- Cash Flow Chart -->
    <!-- Hidden button for automatic refresh when the page is loaded -->
    <button type="button" id="autoRefreshButton" wire:click="updateChart" class="hidden">تحديث</button>
    
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
    
    <x-filament::section heading="رسم بياني للتدفق النقدي" class="mb-6">
        <!-- Chart Controls -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200" wire:key="chart-controls">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Date Range -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">نطاق التاريخ:</label>
                    <div class="flex items-center space-x-2">
                        <input type="date" wire:model.live="chartStartDate" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 py-1 px-2">
                        <span class="text-sm text-gray-500">-</span>
                        <input type="date" wire:model.live="chartEndDate" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 py-1 px-2">
                    </div>
                </div>
                
                <!-- Grouping Type -->
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">الفترة:</label>
                    <select wire:model.live="chartPeriod" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 py-1 px-3">
                        <option value="daily">يومي</option>
                        <option value="weekly">أسبوعي</option>
                        <option value="monthly">شهري</option>
                        <option value="quarterly">ربعي</option>
                        <option value="yearly">سنوي</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="mb-8 flex items-center justify-between" wire:ignore.self>
            <span id="current-chart-type" class="text-sm font-medium text-gray-700">جاري التحميل...</span>
            
            <div class="flex space-x-2" wire:ignore.self>
                <button type="button" 
                    x-data="{}"
                    x-on:click="$wire.$set('chartType', 'line')"
                    class="px-3 py-1 text-xs font-medium rounded-md transition-colors
                    {{ $chartType === 'line' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    خط
                </button>
                <button type="button" 
                    x-data="{}"
                    x-on:click="$wire.$set('chartType', 'bar')"
                    class="px-3 py-1 text-xs font-medium rounded-md transition-colors
                    {{ $chartType === 'bar' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    عمود
                </button>
                <button type="button" 
                    x-data="{}"
                    x-on:click="$wire.$set('chartType', 'stacked')"
                    class="px-3 py-1 text-xs font-medium rounded-md transition-colors
                    {{ $chartType === 'stacked' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    مكدس
                </button>
            </div>
        </div>
        <div class="h-80" wire:ignore>
            <canvas id="cash-flow-chart" class="h-full w-full"></canvas>
        </div>

        <!-- Table View -->
        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">الفترة أو المدة</th>
                        <th scope="col" class="px-6 py-3 text-right">الإيرادات</th>
                        <th scope="col" class="px-6 py-3 text-right">المصروفات</th>
                        <th scope="col" class="px-6 py-3 text-right">صافي التدفق النقدي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashFlowData as $period)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row" class="px-6 py-4 font-medium whitespace-nowrap">
                            @php
                                $periodText = $period->period;
                                $monthNames = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "اغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];
                                
                                // Haftalık format için (2025-W06)
                                if (preg_match('/^(\d{4})-W(\d{1,2})$/', $period->period, $matches)) {
                                    $year = $matches[1];
                                    $week = $matches[2];
                                    $dto = new DateTime();
                                    $dto->setISODate($year, $week);
                                    $weekStart = $dto->format('d.m.Y');
                                    $dto->modify('+6 days');
                                    $weekEnd = $dto->format('d.m.Y');
                                    $periodText = "$weekStart - $weekEnd (Hafta $week)";
                                }
                                // Aylık format için (2025-01)
                                elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $period->period, $matches)) {
                                    $year = $matches[1];
                                    $month = (int)$matches[2];
                                    if ($month >= 1 && $month <= 12) {
                                        $monthName = $monthNames[$month-1];
                                        $periodText = "$monthName $year";
                                    }
                                }
                                // Çeyreklik format için (2025-Q1)
                                elseif (preg_match('/^(\d{4})-Q(\d{1})$/', $period->period, $matches)) {
                                    $year = $matches[1];
                                    $quarter = (int)$matches[2];
                                    $startMonth = ($quarter - 1) * 3 + 1;
                                    $endMonth = $startMonth + 2;
                                    
                                    if ($startMonth >= 1 && $startMonth <= 12 && $endMonth >= 1 && $endMonth <= 12) {
                                        $startMonthName = $monthNames[$startMonth-1];
                                        $endMonthName = $monthNames[$endMonth-1];
                                        $periodText = "$quarter. Çeyrek $year: $startMonthName - $endMonthName";
                                    } else {
                                        $periodText = "$quarter. Çeyrek $year";
                                    }
                                }
                            @endphp
                            {{ $periodText }}
                        </th>
                        <td class="px-6 py-4 text-right text-green-500">
                            {{ number_format($period->inflow, 2, ',', '.') }} ر.ي
                        </td>
                        <td class="px-6 py-4 text-right text-red-500">
                            {{ number_format($period->outflow, 2, ',', '.') }} ر.ي
                        </td>
                        <td class="px-6 py-4 text-right {{ $period->net >= 0 ? 'text-blue-500' : 'text-red-500' }} font-medium">
                            {{ number_format($period->net, 2, ',', '.') }} ر.ي
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                 لم يتم العثور عليه.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($cashFlowData->isNotEmpty())
                <tfoot>
                    <tr class="font-semibold bg-gray-50 dark:bg-gray-700">
                        <th scope="row" class="px-6 py-3">الإجمالي</th>
                        <td class="px-6 py-3 text-right text-green-600">
                            {{ number_format($cashFlowData->sum('inflow'), 2, ',', '.') }} ر.ي
                        </td>
                        <td class="px-6 py-3 text-right text-red-600">
                            {{ number_format($cashFlowData->sum('outflow'), 2, ',', '.') }} ر.ي
                        </td>
                        <td class="px-6 py-3 text-right {{ $cashFlowData->sum('net') >= 0 ? 'text-blue-600' : 'text-red-600' }} font-bold">
                            {{ number_format($cashFlowData->sum('net'), 2, ',', '.') }} ر.ي
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
    
    <!-- Stats Overview Widget -->
    <div class="mt-8 mb-8 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
        <!-- Net Cash Flow -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 {{ $netCashFlow >= 0 ? 'bg-green-600' : 'bg-red-600' }}">
                <h3 class="text-sm font-medium text-white">صافي التدفق النقدي</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold {{ $netCashFlow >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($netCashFlow, 2, ',', '.') }} ر.ي
                </p>
                <p class="text-sm mt-1 text-gray-600">
                    {{ $netCashFlow >= 0 ? 'التدفق النقدي الإيجابي' : 'التدفق النقدي السلبي' }}
                </p>
            </div>
        </div>
        
        <!-- Total Income -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-blue-600">
                <h3 class="text-sm font-medium text-white">إجمالي الإيرادات</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold text-blue-600">
                    {{ number_format($totalInflow, 2, ',', '.') }} ر.ي
                </p>
                <p class="text-sm mt-1 text-gray-600">
                خلال الفترة المحددة
                </p>
            </div>
        </div>
        
        <!-- Total Expense -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-orange-500">
                <h3 class="text-sm font-medium text-white">إجمالي المصروفات</h3>
            </div>
            <div class="p-4">
                <p class="text-2xl font-bold text-orange-500">
                    {{ number_format($totalOutflow, 2, ',', '.') }} ر.ي
                </p>
                <p class="text-sm mt-1 text-gray-600">
                خلال الفترة المحددة
                </p>
            </div>
        </div>
    </div>
    
    <!-- Cash Flow Health -->
    @if(isset($cashFlowSummary['health']))
    <div class="mb-8 bg-white rounded-lg shadow-sm overflow-hidden">
        @php
            $healthIcon = 'exclamation-circle';
            $healthColor = 'bg-red-600';
            $healthText = 'Kritik';
            $healthTextColor = 'text-red-600';
            
            if ($cashFlowSummary['health'] == 'excellent') {
                $healthIcon = 'check-circle';
                $healthColor = 'bg-green-600';
                $healthText = 'ممتازة';
                $healthTextColor = 'text-green-600';
            } elseif ($cashFlowSummary['health'] == 'good') {
                $healthIcon = 'check-circle';
                $healthColor = 'bg-blue-600';
                $healthText = 'جيده';
                $healthTextColor = 'text-blue-600';
            } elseif ($cashFlowSummary['health'] == 'adequate') {
                $healthIcon = 'information-circle';
                $healthColor = 'bg-yellow-600';
                $healthText = 'كافٍ';
                $healthTextColor = 'text-yellow-600';
            } elseif ($cashFlowSummary['health'] == 'warning') {
                $healthIcon = 'exclamation-triangle';
                $healthColor = 'bg-orange-600';
                $healthText = 'يتطلب الحذر';
                $healthTextColor = 'text-orange-600';
            }
        @endphp
        
        <!-- Title -->
        <div class="px-4 py-3 {{ $healthColor }}">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                    @if($healthIcon == 'check-circle')
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    @elseif($healthIcon == 'information-circle')
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    @elseif($healthIcon == 'exclamation-triangle')
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    @else
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    @endif
                </svg>
                <h3 class="text-sm font-medium text-white">صحة التدفق النقدي</h3>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xl font-bold {{ $healthTextColor }}">{{ $healthText }}</span>
                
                <!-- Health Indicator -->
                <div class="flex space-x-1">
                    <div class="w-3 h-3 rounded-full {{ $cashFlowSummary['health'] == 'excellent' || $cashFlowSummary['health'] == 'good' || $cashFlowSummary['health'] == 'adequate' || $cashFlowSummary['health'] == 'warning' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    <div class="w-3 h-3 rounded-full {{ $cashFlowSummary['health'] == 'excellent' || $cashFlowSummary['health'] == 'good' || $cashFlowSummary['health'] == 'adequate' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    <div class="w-3 h-3 rounded-full {{ $cashFlowSummary['health'] == 'excellent' || $cashFlowSummary['health'] == 'good' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    <div class="w-3 h-3 rounded-full {{ $cashFlowSummary['health'] == 'excellent' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                </div>
            </div>
            
            <p class="text-gray-700 mb-4">{{ $cashFlowSummary['healthMessage'] }}</p>
            
            @if(count($cashFlowSummary['recommendations'] ?? []) > 0)
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Öneriler:</h4>
                <ul class="list-disc pl-5 space-y-2 text-gray-600">
                    @foreach($cashFlowSummary['recommendations'] as $recommendation)
                    <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif

</x-table.table-layout>
</div>

@push('scripts')
    <!-- Loading indicator function -->
    <script>
        function toggleChartLoadingIndicator(show) {
            const indicator = document.getElementById('chart-loading-indicator');
            if (indicator) {
                if (show) {
                    indicator.classList.remove('hidden');
                    indicator.classList.add('flex');
                } else {
                    indicator.classList.add('hidden');
                    indicator.classList.remove('flex');
                }
            }
        }
    </script>
    
    <!-- Page load and chart creation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Automatic refresh when the page is loaded
            setTimeout(() => {
                const autoRefreshButton = document.getElementById('autoRefreshButton');
                if (autoRefreshButton) {
                    console.log('Otomatik جديدleme butonu tıklanıyor...');
                    autoRefreshButton.click();
                } else {
                    console.error('Otomatik جديدleme butonu لم يتم العثور عليه!');
                }
            }, 500);
            
            // Update chart type text
            function updateChartTypeText(chartType) {
                const chartTypeText = document.getElementById('current-chart-type');
                if (chartTypeText) {
                    let typeText = '';
                    switch(chartType) {
                        case 'line':
                            typeText = 'Çizgi Grafik';
                            break;
                        case 'bar':
                            typeText = 'Çubuk Grafik';
                            break;
                        case 'stacked':
                            typeText = 'Yığılmış Grafik';
                            break;
                        default:
                            typeText = 'Grafik';
                    }
                    chartTypeText.textContent = typeText;
                }
            }
            
            // Watch date and period changes automatically
            const chartStartDateInput = document.querySelector('input[wire\\:model="chartStartDate"]');
            const chartEndDateInput = document.querySelector('input[wire\\:model="chartEndDate"]');
            const chartPeriodSelect = document.querySelector('select[wire\\:model="chartPeriod"]');
            
            if (chartStartDateInput) {
                chartStartDateInput.addEventListener('change', function() {
                    setTimeout(() => {
                        const autoRefreshButton = document.getElementById('autoRefreshButton');
                        if (autoRefreshButton) autoRefreshButton.click();
                    }, 100);
                });
            }
            
            if (chartEndDateInput) {
                chartEndDateInput.addEventListener('change', function() {
                    setTimeout(() => {
                        const autoRefreshButton = document.getElementById('autoRefreshButton');
                        if (autoRefreshButton) autoRefreshButton.click();
                    }, 100);
                });
            }
            
            if (chartPeriodSelect) {
                chartPeriodSelect.addEventListener('change', function() {
                    setTimeout(() => {
                        const autoRefreshButton = document.getElementById('autoRefreshButton');
                        if (autoRefreshButton) autoRefreshButton.click();
                    }, 100);
                });
            }
            
            // Listen to Livewire events
            window.Livewire.on('cashFlowDataUpdated', (data) => {
                console.log('نقدًا akışı verileri güncellendi', data);
                
                // Check data structure
                if (data && data.chartType && data.chartData) {
                    console.log('Grafik verileri:', data.chartData);
                    updateChartTypeText(data.chartType);
                    renderChart(data.chartType, data.chartData);
                } else {
                    console.error('Grafik verileri eksik veya hatalı:', data);
                }
            });
            
            // Hide loading indicator when the chart is created
            document.addEventListener('chartRendered', () => {
                console.log('Grafik أنشاءuldu eventi alındı');
                toggleChartLoadingIndicator(false);
            });
        });
        
        // Chart creation function
        function renderChart(chartType, chartData) {
            toggleChartLoadingIndicator(true);
            
            // Check data
            if (!chartData || typeof chartData !== 'object') {
                console.error('Geçersiz grafik verisi:', chartData);
                toggleChartLoadingIndicator(false);
                return;
            }
            
            // Add a short delay before creating the chart
            setTimeout(() => {
                try {
                    const ctx = document.getElementById('cash-flow-chart');
                    if (!ctx) {
                        console.error('Grafik canvas لم يتم العثور عليه');
                        toggleChartLoadingIndicator(false);
                        return;
                    }
                    
                    // Destroy existing chart if it exists
                    if (window.cashFlowChart instanceof Chart) {
                        window.cashFlowChart.destroy();
                    }
                    
                    // Prepare chart data
                    const datasets = [];
                    
                    // Check data
                    const labels = chartData.labels || [];
                    const inflowData = chartData.inflowData || [];
                    const outflowData = chartData.outflowData || [];
                    const netData = chartData.netData || [];
                    
                    console.log('Grafik veri yapısı:', {
                        labels: labels.length,
                        inflowData: inflowData.length,
                        outflowData: outflowData.length,
                        netData: netData.length
                    });
                    
                    // Income data set
                    datasets.push({
                        label: 'الإيرادات',
                        data: inflowData,
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 2,
                        tension: 0.1
                    });
                    
                    // Expense data set
                    datasets.push({
                        label: 'المصروفات',
                        data: outflowData,
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 2,
                        tension: 0.1
                    });
                    
                    // Net cash flow data set
                    datasets.push({
                        label: 'صافي التدفق النقدي',
                        data: netData,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2,
                        tension: 0.1,
                        // For line chart, always show as line
                        type: chartType === 'stacked' ? 'line' : undefined
                    });
                    
                    // Chart configuration
                    const config = {
                        type: chartType === 'stacked' ? 'bar' : chartType,
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'YER' }).format(context.parsed.y);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'YER', maximumFractionDigits: 0 }).format(value);
                                        }
                                    }
                                }
                            }
                        }
                    };
                    
                    // Special settings for stacked chart
                    if (chartType === 'stacked') {
                        config.options.scales.y.stacked = true;
                        config.options.scales.x.stacked = true;
                        
                        // Show net cash flow as line in stacked chart
                        datasets[2].type = 'line';
                        datasets[2].fill = false;
                        datasets[2].order = 0; // Bring line to the front
                    }
                    
                    // Create chart
                    window.cashFlowChart = new Chart(ctx, config);
                    
                    // Trigger chartRendered event
                    document.dispatchEvent(new CustomEvent('chartRendered'));
                } catch (error) {
                    console.error('Grafik أنشاءulurken hata:', error);
                    toggleChartLoadingIndicator(false);
                }
            }, 300);
        }
    </script>
@endpush