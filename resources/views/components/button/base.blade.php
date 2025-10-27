@props([
    'type' => 'button',
    'color' => 'primary',
])

<button 
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center px-4 py-2 border rounded-lg text-sm font-medium ' . 
        match($color) {
            'primary' => 'border-transparent bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
            'white' => 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary-500',
            'red' => 'border-transparent bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            default => 'border-transparent bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        }
    ]) }}
>
    {{ $slot }}
</button> 