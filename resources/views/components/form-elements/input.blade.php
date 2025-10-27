@props([
    'label' => null,
    'type' => 'text',
])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif

    <input 
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm']) }}
    />
</div> 