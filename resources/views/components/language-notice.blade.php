<div class="bg-white border rounded-lg shadow-sm mb-6">
    <div class="flex items-center p-4 border-b">
        <div class="flex-shrink-0">
            @if($defaultLocale === $locale)
                <div class="p-2 bg-primary-50 rounded-lg">
                    <x-heroicon-s-globe-alt class="w-5 h-5 text-primary-500"/>
                </div>
            @else
                <div class="p-2 bg-warning-50 rounded-lg">
                    <x-heroicon-s-language class="w-5 h-5 text-warning-500"/>
                </div>
            @endif
        </div>
        
        <div class="ml-4">
            <h3 class="text-base font-medium text-gray-900">
                @if($defaultLocale === $locale)
                    Managing Default Language Content
                @else
                    Managing Translation Content
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                You are currently <span class="font-medium">{{ isset($isEdit) && $isEdit ? 'editing' : 'adding' }}</span> content in 
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $defaultLocale === $locale ? 'bg-primary-100 text-primary-800' : 'bg-warning-100 text-warning-800' }}">
                    {{ strtoupper($locale) }}
                </span>
            </p>
        </div>
    </div>

    @if($defaultLocale === $locale)
        <div class="px-4 py-3 bg-gray-50 text-sm text-gray-500">
            This is your default language. Content in this language will be used as fallback when translations are not available.
        </div>
    @endif
</div> 