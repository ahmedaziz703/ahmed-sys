<section class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-[#E5E7EB]">
    <div class="w-full max-w-md relative">
        
        <!-- Main card -->
        <div class="relative bg-white backdrop-filter backdrop-blur-sm bg-opacity-90 rounded-2xl shadow-xl overflow-hidden border border-gray-100 z-10">
            <!-- Make the top more colorful and attractive -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-500 pt-6 pb-10 px-8 rounded-b-[40px] relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                <div class="absolute -left-6 bottom-2 w-20 h-20 bg-white opacity-10 rounded-full"></div>
                
                @php
                    $logo = \App\Models\Setting::where('group', 'site')->where('key', 'site_logo')->first();
                    $logoPath = $logo ? $logo->value : 'site/logo.svg';
                    $siteTitle = \App\Models\Setting::where('group', 'site')->where('key', 'site_title')->first();
                    $siteName = $siteTitle ? $siteTitle->value : 'نظام إدارة الإيرادات والمصروفات';
                @endphp
                
                <div class="flex items-center justify-center mb-3">
                    <img class="h-14 filter brightness-0 invert" src="{{ asset($logoPath) }}" alt="{{ $siteName }}">
                </div>
                <h2 class="text-2xl font-bold text-white text-center">{{ $siteName }}</h2>
                <p class="text-primary-100 text-center text-sm mt-1">قم بتسجيل الدخول الآمن إلى بياناتك المالية</p>
            </div>
            
            <!-- Form section - shifted position -->
            <div class="px-8 pb-8 -mt-6">
                <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all">
                    <form class="space-y-5" wire:submit.prevent="submit" method="POST">
                        @csrf
                        
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-700">البريد الإلكتروني</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                </div>
                                <input type="email" name="email" wire:model="email" id="email-field"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 transition-all" 
                                    placeholder="email@example.com">
                            </div>
                            @error('email')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-700">كلمة المرور</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="password" name="password" wire:model="password" id="password-field"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 transition-all" 
                                    placeholder="••••••••">
                            </div>
                            @error('password')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-primary-500 text-primary-600">
                                <label for="remember" class="ml-2 text-sm text-gray-600">تذكرني</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 text-white font-medium rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-150">
                            <span class="flex items-center justify-center">
                                تسجيل الدخول
                            </span>
                        </button>
                        
                        @if(config('app.app_demo_mode', false))
                        <div class="pt-4 space-y-3">
                            <p class="text-center text-sm font-medium text-gray-700">تسجيل دخول تجريبي</p>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" id="admin-login" 
                                    class="py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition-all duration-150">
                                    دخول المدير
                                </button>
                                <button type="button" id="employee-login"
                                    class="py-2 px-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition-all duration-150">
                                    دخول الموظف
                                </button>
                            </div>
                        </div>
                        @endif
                    </form>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        {{ date('Y') }} &copy; {{ $siteName }} - جميع الحقوق محفوظة.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@if(config('app.app_demo_mode', false))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Admin login button
        document.getElementById('admin-login').addEventListener('click', function() {
            // Fill admin credentials
            document.getElementById('email-field').value = 'admin@admin.com';
            document.getElementById('password-field').value = 'admin123';
            
            // Update Livewire model directly through dispatchEvent
            document.getElementById('email-field').dispatchEvent(new Event('input'));
            document.getElementById('password-field').dispatchEvent(new Event('input'));
            
            // Submit the form
            setTimeout(function() {
                document.querySelector('form button[type="submit"]').click();
            }, 300);
        });
        
        // Employee login button
        document.getElementById('employee-login').addEventListener('click', function() {
            // Fill employee credentials
            document.getElementById('email-field').value = 'test@test.com';
            document.getElementById('password-field').value = 'testtest';
            
            // Update Livewire model directly through dispatchEvent
            document.getElementById('email-field').dispatchEvent(new Event('input'));
            document.getElementById('password-field').dispatchEvent(new Event('input'));
            
            // Submit the form
            setTimeout(function() {
                document.querySelector('form button[type="submit"]').click();
            }, 300);
        });
    });
</script>
@endif