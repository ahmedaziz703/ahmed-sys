@props([
    'href',
    'label',
    'attributes' => []
])

<a href="{{ $href }}" 
   {{ $attributes->merge([
        'class' => 'text-gray-900 hover:text-primary-600 transition-colors duration-200'
   ]) }}
   @if(isset($attributes['wire:navigate']))
   wire:navigate
   @endif
>
    {{ $label }}
</a> 