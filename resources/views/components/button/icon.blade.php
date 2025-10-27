@props([
    'icon' => null,
])

<button 
    type="button"
    {{ $attributes->merge(['class' => 'p-1 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500']) }}
>
    <x-dynamic-component 
        :component="'heroicon-m-' . $icon"
        class="w-5 h-5 text-gray-400"
    />
</button> 