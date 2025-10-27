@props(['name', 'icon', 'color'])

<div class="flex items-center gap-2">
    <div class="w-5 h-5 rounded-full flex items-center justify-center" style="background-color: {{ $color ?? '#4B5563' }}">
    </div>
    <span>{{ $name }}</span>
</div> 