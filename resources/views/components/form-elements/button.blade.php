@props([
    'type' => 'button',
    'color' => 'primary',
])

<button 
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white ' . 
        match($color) {
            'primary' => 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500',
            'gray' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
            'white' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary-500 rounded-lg',
            default => 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500',
        }
    ]) }}
>
    {{ $slot }}
</button> 