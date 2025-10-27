<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث البيانات التجريبية</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <style>
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto p-8 bg-white rounded-xl shadow-lg">
        <div class="text-center">
            <!-- Logo -->
            @php
                $logo = \App\Models\Setting::where('group', 'site')->where('key', 'site_logo')->first();
                $logoPath = $logo ? $logo->value : 'site/logo.svg';
                $siteTitle = \App\Models\Setting::where('group', 'site')->where('key', 'site_title')->first();
                $siteName = $siteTitle ? $siteTitle->value : 'نظام إدارة الإيرادات والمصروفات';
            @endphp
            
            <div class="flex justify-center mb-6">
                <img class="h-16" src="{{ asset($logoPath) }}" alt="{{ $siteName }}">
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-2">تحديث البيانات التجريبية</h1>
            <p class="text-gray-600 mb-6">النظام في وضع الصيانة حالياً. يرجى الانتظار.</p>
            
            <div class="flex justify-center mb-4">
                <div class="w-12 h-12 border-t-4 border-primary-600 border-solid rounded-full animate-spin"></div>
            </div>
            
            <p class="text-gray-500 text-sm animate-pulse-slow">
                قد تستغرق هذه العملية بضع دقائق.<br>
                يرجى تحديث الصفحة أو المحاولة مرة أخرى لاحقاً.
            </p>
            
            <div class="mt-8 text-sm text-gray-400">
                {{ date('Y') }} &copy; {{ $siteName }} - الوضع التجريبي
            </div>
        </div>
    </div>
</body>
</html> 