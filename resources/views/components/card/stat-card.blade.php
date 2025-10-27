@props(['label', 'value', 'color'])

<div class="relative overflow-hidden bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow border-b-4 
    {{ match($color) {
        'info' => 'border-blue-500',
        'success' => 'border-green-500',
        'danger' => 'border-red-500',
        'warning' => 'border-yellow-500',
        'primary' => 'border-indigo-500',
    } }}">
    <div class="p-4 text-center">
        <p class="text-sm font-medium text-gray-500 uppercase tracking-normal">
            {{ $label }}
        </p>
        <h3 class="text-lg font-semibold text-gray-900 mt-2 truncate">
            {{ $value }}
        </h3>
    </div>
</div> 