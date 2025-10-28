<body x-data="{ sidebarOpen: false }" class="relative">
<aside x-data="{
    activeMenu: null,
    menuGroups: {
        'financial_transactions': [
            'admin.transactions.index',
            'admin.transactions.create',
            'admin.transactions.edit',
            'admin.recurring',
            'admin.subscriptions.index', 
            'admin.deposits.index',
            'admin.credit-cards.index',
            'admin.credit-cards.transactions',
            'admin.debts.index',
            'admin.debts.payments',
            'admin.loans.index',
            'admin.loans.payments',
            'admin.loans.details'
        ],
        'customer_management': [
            'admin.customers.index',
            'admin.customers.groups',
            'admin.customers.potential',
            'admin.customers.show'
        ],
        'project_management': [
            'admin.projects.index',
            'admin.projects.active',
            'admin.projects.completed'
        ],
        'debt_system': [
            'admin.debts.index',
            'admin.loans.index'
        ],
        'categories': [
            'admin.categories.index',
        ],
        'analysis_tracking': [
            'admin.analysis.cash-flow',
            'admin.analysis.categories'
        ],
        'planning': [
            'admin.planning.savings',
            'admin.planning.investments'
        ],
        'reports': ['admin.reports.financial', 'admin.reports.customer', 'admin.reports.project', 'admin.reports.tax'],
        'documents': ['admin.documents.contracts', 'admin.documents.proposals', 'admin.documents.templates'],
        'system_settings': [
            'admin.settings', 
            'admin.roles', // Use prefix to cover index, create, edit, etc.
            'admin.users', 
        ],
        'accounts': [
            'admin.accounts.bank',
            'admin.accounts.credit-cards',
            'admin.accounts.crypto',
            'admin.accounts.virtual-pos',
            'admin.accounts.history'
        ]
    },

    init() {
        this.setActiveMenu();
        
        // Livewire navigasyonunu dinle
        Livewire.on('navigated', () => this.setActiveMenu());
    },

    setActiveMenu() {
        const currentRoute = '{{ request()->route()->getName() }}';
        
        // فحص كل مجموعة قوائم
        Object.entries(this.menuGroups).forEach(([group, routes]) => {
            if (routes.some(route => currentRoute.startsWith(route))) {
                this.activeMenu = group;
            }
        });
    },

    isActive(routeName) {
        const currentRoute = '{{ request()->route()->getName() }}';
        
        // فحص خاص لصفحة تفاصيل العميل
        if (routeName === 'admin.customers.index') {
            return currentRoute === 'admin.customers.index' || currentRoute === 'admin.customers.show';
        }
        
        // فحص خاص للمعاملات
        if (routeName === 'admin.transactions.index') {
            return currentRoute.startsWith('admin.transactions.');
        }
        
        // فحص خاص لبطاقات الائتمان
        if (routeName === 'admin.credit-cards') {
            return currentRoute.startsWith('admin.credit-cards.');
        }
        
        // فحص خاص لصفحات المشاريع واللوحات
        if (routeName === 'admin.projects.index') {
            return currentRoute.startsWith('admin.projects.');
        }
        
        // فحص خاص للديون والذمم
        if (routeName === 'admin.debts') {
            return currentRoute.startsWith('admin.debts.');
        }
        
        // فحص خاص للأدوار والصلاحيات
        if (routeName === 'admin.roles.index') {
            return currentRoute.startsWith('admin.roles.');
        }
        
        // فحص خاص للمستخدمين
        if (routeName === 'admin.users.index') {
            return currentRoute.startsWith('admin.users.');
        }
        
        // فحص افتراضي (إذا لم تكن هناك حالة خاصة)
        return currentRoute === routeName;
    },

    // إظهار/إخفاء القوائم حسب صلاحيات المستخدم
    canViewMenu(menu) {
        const permissions = {
            'financial_transactions': ['view_finances', 'manage_finances'],
            'customer_management': ['view_customers', 'manage_customers'],
            'project_management': ['view_projects', 'manage_projects'],
            'invoice_management': ['view_invoices', 'manage_invoices'],
            'analysis_tracking': ['view_reports', 'view_analysis'],
            'user_management': ['manage_staff', 'manage_roles']
        };
        
        // Super Admin و Owner يمكنهما رؤية كل شيء
        if (@json(auth()->user()->hasRole(['super_admin', 'owner']))) {
            return true;
        }
        
    }
}" 
x-bind:class="{'translate-x-0': sidebarOpen, 'translate-x-full': !sidebarOpen}" 
       id="sidebar" 
       class="fixed right-0 top-0 z-40 h-screen pt-16 w-64 bg-white border-l border-gray-200 dark:bg-gray-800 dark:border-gray-700 transition-transform duration-300 lg:translate-x-0" 
       aria-label="Sidebar">
    <!-- Logo Container -->
    @php
        // Cache'den site ayarlarını al (AppServiceProvider'da dolduruluyor)
        $siteSettings = \Illuminate\Support\Facades\Cache::get('site_settings', []);
        $logoPath = $siteSettings['site_logo'] ?? null;
        $logoUrl = ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath))
                   ? \Illuminate\Support\Facades\Storage::url($logoPath)
                   : null; // Varsayılan logo URL'si veya null olabilir
        $defaultLogoUrl = 'https://flowbite.s3.amazonaws.com/logo.svg'; // Varsayılan logo
    @endphp
    <div class="absolute top-0 left-0 right-0 h-16 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex  text-center h-full px-3">
            <img src="{{ $logoUrl ?? $defaultLogoUrl }}" class="h-10 my-auto" alt="Logo" />
        </a>
    </div>

    <div class="h-full px-3 pb-4 overflow-y-auto my-4 sidebar-scroll">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('admin.dashboard') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.dashboard')}">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="ml-3">لوحة التحكم</span>
                </a>
            </li>
            @canany(['transactions.view', 'recurring_transactions.view', 'debts.view', 'loans.view'])
            <li>
                <button @click="activeMenu = activeMenu === 'financial_transactions' ? null : 'financial_transactions'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h.01M11 15h2M6 5h12a3 3 0 013 3v8a3 3 0 01-3 3H6a3 3 0 01-3-3V8a3 3 0 013-3z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">إدارة المالية</span>
                    <svg :class="{'rotate-180': activeMenu === 'financial_transactions'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'financial_transactions'" x-collapse class="py-2 space-y-2">
                    @can('transactions.view')
                    <!-- جميع المعاملات -->
                    <li>
                        <a href="{{ route('admin.transactions.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.transactions.index')}">
                           جميع المعاملات
                        </a>
                    </li>
                    @endcan
                    @can('recurring_transactions.view')
                    <!-- المعاملات المتكررة -->
                    <li>
                        <a href="{{ route('admin.recurring') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.recurring')}">
                           المعاملات المتكررة
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany
            @canany(['customers.view', 'leads.view', 'customer_groups.view'])
            <li></li>
                <button @click="activeMenu = activeMenu === 'customer_management' ? null : 'customer_management'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">العملاء</span>
                    <svg :class="{'rotate-180': activeMenu === 'customer_management'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'customer_management'" x-collapse class="py-2 space-y-2">
                    @can('customers.view')
                    <li>
                        <a href="{{ route('admin.customers.index') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.customers.index')}">
                            قائمة العملاء
                        </a>
                    </li>
                    @endcan
                    @can('leads.view')
                    <li>
                        <a href="{{ route('admin.customers.potential') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.customers.potential')}">
                            العملاء المحتملين
                        </a>
                    </li>
                    @endcan
                    @can('customer_groups.view')
                    <li>
                        <a href="{{ route('admin.customers.groups') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.customers.groups')}">
                            مجموعات العملاء
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany
            @canany(['bank_accounts.view', 'credit_cards.view', 'crypto_wallets.view', 'virtual_pos.view'])
            <li>
                <button @click="activeMenu = activeMenu === 'accounts' ? null : 'accounts'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M3 10h18M12 3L4 9h16l-8-6zM4 10v8M8 10v8M12 10v8M16 10v8M20 10v8"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">الحسابات</span>
                    <svg :class="{'rotate-180': activeMenu === 'accounts'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'accounts'" x-collapse class="py-2 space-y-2">
                    @can('bank_accounts.view')
                    <li>
                        <a href="{{ route('admin.accounts.bank') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.bank') }}' === '1'}">
                           الحسابات المصرفية
                        </a>
                    </li>
                    @endcan
                    @can('credit_cards.view')
                    <li>
                        <a href="{{ route('admin.accounts.credit-cards') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.credit-cards') }}' === '1'}">
                           بطاقات الائتمان
                        </a>
                    </li>
                    @endcan
                    @can('crypto_wallets.view')
                    <li>
                        <a href="{{ route('admin.accounts.crypto') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.crypto') }}' === '1'}">
                           محافظ العملات المشفرة
                        </a>
                    </li>
                    @endcan
                    @can('virtual_pos.view')
                    <li>
                        <a href="{{ route('admin.accounts.virtual-pos') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.virtual-pos') }}' === '1'}">
                           نقاط البيع الافتراضية
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            @canany(['reports.cash_flow', 'reports.category_analysis'])
            <li>
                <button @click="activeMenu = activeMenu === 'analysis_tracking' ? null : 'analysis_tracking'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">التحليل والتقارير</span>
                    <svg :class="{'rotate-180': activeMenu === 'analysis_tracking'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'analysis_tracking'" x-collapse class="py-2 space-y-2">
                    @can('reports.cash_flow')
                    <li>
                        <a href="{{ route('admin.analysis.cash-flow') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.analysis.cash-flow')}">
                           التدفق النقدي
                        </a>
                    </li>
                    @endcan
                    @can('reports.category_analysis')
                    <li>
                        <a href="{{ route('admin.analysis.categories') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.analysis.categories')}">
                            تحليل الفئات
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany
            @canany(['savings.view', 'investments.view'])
            <li>
                <button @click="activeMenu = activeMenu === 'planning' ? null : 'planning'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">الخطط المالية</span>
                    <svg :class="{'rotate-180': activeMenu === 'planning'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'planning'" x-collapse class="py-2 space-y-2">
                    @can('savings.view')
                    <li>
                        <a href="{{ route('admin.planning.savings') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.planning.savings')}">
                           خطة الادخار
                        </a>
                    </li>
                    @endcan
                    @can('investments.view')
                    <li>
                        <a href="{{ route('admin.planning.investments') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.planning.investments')}">
                           خطط الاستثمار
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany
            @canany(['debts.view', 'loans.view'])
            <li>
                <button @click="activeMenu = activeMenu === 'debt_system' ? null : 'debt_system'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">إدارة الديون</span>
                    <svg :class="{'rotate-180': activeMenu === 'debt_system'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'debt_system'" x-collapse class="py-2 space-y-2">
                    <!-- الديون والذمم -->
                    @can('debts.view')
                    <li>
                        <a href="{{ route('admin.debts.index') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.debts.index') }}' === '1'}">
                           الديون والذمم
                        </a>
                    </li>
                    @endcan
                    <!-- القروض -->
                    @can('loans.view')
                    <li>
                        <a href="{{ route('admin.loans.index') }}"
                           wire:navigate
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.loans.index') }}' === '1'}">
                           القروض
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany
            @can('suppliers.view')
            <li>
                <a href="{{ route('admin.suppliers.index') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.suppliers.index')}">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span class="ml-3">الموردون</span>
                </a>
            </li>
            @endcan
            @can('categories.view')
            <li>
                <a href="{{ route('admin.categories.index') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.categories.index')}">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span class="ml-3">الفئات</span>
                </a>
            </li>
            @endcan
            @can(['projects.view'])
            <li>
                <a href="{{ route('admin.projects.index') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.projects.index')}">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="ml-3">المشاريع</span>
                </a>
            </li>
            @endcan
            @canany(['settings.view','settings.site', 'settings.notification', 'settings.telegram', 'roles.view', 'users.view'])
            <li>
                <button @click="activeMenu = activeMenu === 'system_settings' ? null : 'system_settings'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-right">النظام</span>
                    <svg :class="{'rotate-180': activeMenu === 'system_settings'}" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'system_settings'" x-collapse class="py-2 space-y-2">
                    @canany('settings.view')
                    <li>
                        <a href="{{ route('admin.settings.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.settings.index')}">
                           الإعدادات
                        </a>
                    </li>
                    @endcanany
                    @can('roles.view')
                    <li>
                        <a href="{{ route('admin.roles.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.roles.index')}">
                           الأدوار والأذونات
                        </a>
                    </li>
                    @endcan
                    @can('users.view')
                    <li>
                        <a href="{{ route('admin.users.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.users.index')}">
                            المستخدمون
                        </a>
                    </li>
                    @endcan
                    <li>
                        <a href="https://wa.me/967775119035" 
                           target="_blank"
                           class="flex items-center p-2 pl-11 w-full text-base font-normal rounded-lg hover:bg-gray-100"
                           style="color: #3E82F8">
                           المساعدة
                        </a>
                    </li>
                </ul>
            </li>
            @endcanany
        </ul>
    </div>
</aside>

