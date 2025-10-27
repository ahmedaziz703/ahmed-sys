@props([
    'title' => null,
    'maxWidth' => '2xl'
])

<div
    x-data="{ show: @entangle($attributes->wire('model')), maxWidth: '{{ $maxWidth }}' }"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50"
    style="display: none;"
>
    <div 
        x-show="show"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="show = false"
    ></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                x-show="show"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-on:click.stop
                :class="{
                    'sm:max-w-sm': maxWidth === 'sm',
                    'sm:max-w-md': maxWidth === 'md',
                    'sm:max-w-lg': maxWidth === 'lg',
                    'sm:max-w-xl': maxWidth === 'xl',
                    'sm:max-w-2xl': maxWidth === '2xl',
                    'sm:max-w-3xl': maxWidth === '3xl',
                    'sm:max-w-4xl': maxWidth === '4xl',
                    'sm:max-w-5xl': maxWidth === '5xl',
                    'sm:max-w-6xl': maxWidth === '6xl',
                    'sm:max-w-7xl': maxWidth === '7xl',
                }"
            >
                @if($title)
                    <div class="bg-white px-4 py-3 border-b">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $title }}
                        </h3>
                    </div>
                @endif

                <div class="bg-white px-4 py-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div> 