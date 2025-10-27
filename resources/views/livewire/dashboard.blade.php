<x-table.table-layout 
    pageTitle="لوحة التحكم"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true, 'icon' => 'fas fa-home'],
        ['label' => 'التحليل العام']
    ]"
>
@php
    $colors = [
        'income' => '#10B981',
        'expense' => '#EF4444',
        'profit' => '#6366F1',
        'customer' => '#F59E0B',
        'project' => '#8B5CF6',
        'account' => '#3B82F6'
    ];
@endphp

<div>
    @if (auth()->user()->hasRole('admin'))
        {{-- Admin Dashboard --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {{-- Total Lead Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">إجمالي العملاء المحتملين</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Potential Lead Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">العملاء المحتملين النشطين</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['potential_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Negotiated Lead Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">العملاء المحتملين قيد التفاوض</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['negotiating_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Commission Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">إجمالي العمولة هذا الشهر</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_commission'], 2) }} ر.ي</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Paid Commission Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">المدفوعات هذا الشهر</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_commission_paid'], 2) }} ر.ي</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pending Commission Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">المدفوعات المعلقة هذا الشهر</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_commission'], 2) }} ر.ي</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div id="dashboard-charts" class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:ignore>
            {{-- Income/Expense Chart --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تحليل الإيرادات/المصروفات</h3>
                <div class="relative h-[300px]">
                    <canvas id="income-expense-chart"></canvas>
                </div>
            </div>

            {{-- Customer Growth Chart --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تحليل نمو العملاء</h3>
                <div class="relative h-[300px]">
                    <canvas id="customer-growth-chart"></canvas>
                </div>
            </div>
        </div>

    @else
        {{-- Employee Dashboard --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            {{-- Pending Activities Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">الأنشطة المعلقة</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_activities'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Earned Commission Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">المكاسب هذا الشهر</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['earned_commission'], 2) }} $</p>
                    </div>
                </div>
            </div>

            {{-- Paid Commission Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">المدفوعات هذا الشهر</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['paid_commission'], 2) }} ر.ي</p>
                    </div>
                </div>
            </div>

            {{-- Total Commission Widget --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">إجمالي العمولة</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_commission'], 2) }} $</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Last Notes --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">آخر الملاحظات</h3>
            <div class="space-y-4">
                @foreach($customerNotes as $note)
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        @switch($note['type'])
                            @case('call')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </span>
                                @break
                            @case('meeting')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </span>
                                @break
                            @case('email')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </span>
                        @endswitch
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.customers.show', $note['customer']['id']) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                {{ $note['customer']['name'] }}
                            </a>
                            <span @class([
                                'text-xs px-2 py-1 rounded-full',
                                'bg-yellow-100 text-yellow-800' => $note['is_upcoming']
                            ])>
                                {{ $note['formatted_date'] }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">{{ Str::limit($note['content'], 100) }}</p>
                        <div class="mt-0.5 flex items-center space-x-2 text-xs text-gray-400">
                            <span>{{ Carbon\Carbon::parse($note['created_at'])->diffForHumans() }}</span>
                            <span>•</span>
                            <span>{{ $note['user']['name'] }}</span>
                            @if(isset($note['assigned_user']))
                                <span>→ {{ $note['assigned_user']['name'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if(auth()->user()->has_commission)
        {{-- Commission Chart --}}
        <div class="bg-white rounded-xl shadow-sm p-6" wire:ignore>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">تحليل العمولة</h3>
            <div class="relative h-[300px]">
                <canvas id="commission-chart"></canvas>
            </div>
        </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    // Wait for Chart.js to load
    function waitForChart(callback, maxAttempts = 10) {
        let attempts = 0;
        const checkChart = setInterval(() => {
            attempts++;
            if (window.Chart) {
                clearInterval(checkChart);
                callback();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkChart);
                console.error('لم يتم تحميل Chart.js');
            }
        }, 100);
    }

    // Define chart variables in global scope
    window.dashboardCharts = {
        incomeExpense: null,
        customerGrowth: null,
        performance: null,
        commission: null
    };

    function destroyDashboardCharts() {
        // First clear existing charts
        Object.values(window.dashboardCharts).forEach(chart => {
            if (chart instanceof window.Chart) {
                chart.destroy();
            }
        });

        // Reset chart references
        window.dashboardCharts = {
            incomeExpense: null,
            customerGrowth: null,
            performance: null,
            commission: null
        };
    }

    function initDashboardCharts() {
        if (!window.Chart) {
            console.error('Chart.js غير محمل');
            return;
        }

        // First clear all charts
        destroyDashboardCharts();

        const commissionCtx = document.getElementById('commission-chart');
        const customerGrowthCtx = document.getElementById('customer-growth-chart');
        const incomeExpenseCtx = document.getElementById('income-expense-chart');

        // Common chart options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 0
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 10,
                        boxWidth: 15,
                        usePointStyle: true
                    }
                }
            }
        };

        // Admin charts
        if (incomeExpenseCtx && customerGrowthCtx) {
            const data = @json($chartData);
            const customerData = @json($customerGrowthData);
            window.dashboardCharts.incomeExpense = new window.Chart(incomeExpenseCtx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.month),
                    datasets: [
                        {
                            label: 'الإيرادات',
                            data: data.map(item => item.income || 0),
                            backgroundColor: '#10B981',
                            borderColor: '#10B981',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'المصروفات',
                            data: data.map(item => item.expense || 0),
                            backgroundColor: '#EF4444',
                            borderColor: '#EF4444',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('tr-TR', {
                                        style: 'currency',
                                        currency: 'YER',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            window.dashboardCharts.customerGrowth = new window.Chart(customerGrowthCtx, {
                type: 'line',
                data: {
                    labels: customerData.map(item => item.month),
                    datasets: [{
                        label: 'العملاء الجدد',
                        data: customerData.map(item => item.total || 0),
                        borderColor: '#F59E0B',
                        backgroundColor: '#FEF3C7',
                        fill: true,
                        tension: 0.4,
                        pointStyle: 'circle',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Commission chart (employee)
        if (commissionCtx) {
            const data = @json($commissionData ?? []);
            window.dashboardCharts.commission = new window.Chart(commissionCtx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.month),
                    datasets: [
                        {
                            label: 'العمولة المكتسبة',
                            data: data.map(item => item.earned || 0),
                            backgroundColor: '#10B981',
                            borderColor: '#10B981',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'العمولة المدفوعة',
                            data: data.map(item => item.paid || 0),
                            backgroundColor: '#3B82F6',
                            borderColor: '#3B82F6',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('tr-TR', {
                                        style: 'currency',
                                        currency: 'YER',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }

    // When page loads and Livewire navigations, initialize charts
    document.addEventListener('livewire:navigated', () => {
        waitForChart(initDashboardCharts);
    });

    // First load
    waitForChart(initDashboardCharts);
</script>
@endpush 
</x-table.table-layout> 
