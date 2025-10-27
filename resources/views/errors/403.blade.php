<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erişim Engellendi</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Nunito', sans-serif;
        }
    </style>
</head>
<body class="antialiased bg-gray-100">
    <div class="relative flex items-top justify-center min-h-screen sm:items-center py-4 sm:pt-0">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">
                    403
                </div>
                <div class="ml-4 text-lg text-gray-500 uppercase tracking-wider">
                    Erişim Engellendi
                </div>
            </div>
            <div class="mt-6 text-center text-gray-600">
                Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.
                <br>
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-blue-600 hover:text-blue-900">Anasayfaya Dön</a>
            </div>
        </div>
    </div>
</body>
</html>