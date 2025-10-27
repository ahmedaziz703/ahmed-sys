<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Cache'den site ayarlarını al (AppServiceProvider'da dolduruluyor)
        $siteSettings = \Illuminate\Support\Facades\Cache::get('site_settings', []);
        $siteTitle = $siteSettings['site_title'] ?? config('app.name', 'Laravel');
        $faviconPath = $siteSettings['site_favicon'] ?? null;
        $faviconUrl = ($faviconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath))
                      ? \Illuminate\Support\Facades\Storage::url($faviconPath)
                      : null;
    @endphp

    <title>{{ $siteTitle }}</title> 

    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @livewireStyles
    @stack('styles')

    <style>
        /* Tüm toggle sütunlarını küçültme */
        .fi-ta-col-toggle .fi-ta-toggle {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        /* Toggle düğmesinin kendisini küçültme */
        .fi-toggle {
            --toggle-width: 2.5rem !important;
            --toggle-height: 1.25rem !important;
        }

        /* Toggle içindeki yuvarlak düğmeyi küçültme */
        .fi-toggle-dot {
            --toggle-dot-size: 0.875rem !important;
        }
    </style>

</head>
<body class="bg-gray-100 font-sans antialiased"> 
    @auth
        @include('components.partials.header')
        @include('components.partials.sidebar')

        <main class="lg:mr-64 pt-16">
            <div class="p-4">
                <div class="w-full overflow-hidden">
                    {{ $slot }}
                </div>
            </div>
        </main>
        
        {{-- AI Chat Widget --}}
        {{-- <livewire:ai.chat-widget /> --}}
    @else
        {{ $slot }}
    @endauth

    @livewireScripts
    @filamentScripts

    @livewire('notifications')

    @stack('scripts')
</body>
</html>
