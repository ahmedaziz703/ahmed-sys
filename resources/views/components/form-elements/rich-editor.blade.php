@props([
    'label' => null,
])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif

    <div class="border border-gray-300 rounded-lg overflow-hidden">
        <textarea
            {{ $attributes->merge(['class' => 'block w-full rounded-md border-0 p-3 text-gray-900 shadow-sm ring-0 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 min-h-[150px]']) }}
        ></textarea>
    </div>

    <div class="mt-1 text-xs text-gray-500">
        الحد الأقصى 2000 حرف
    </div>
</div> 