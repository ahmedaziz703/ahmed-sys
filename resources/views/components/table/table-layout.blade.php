@props([
    'pageTitle' => '',
    'breadcrumbs' => [],
    'backRoute' => null,
    'backLabel' => null,
])
<div>
    <!-- Header Section -->
    <x-layouts.page-header 
        :pageTitle="$pageTitle"
        :breadcrumbs="$breadcrumbs"
        :backRoute="$backRoute"
        :backLabel="$backLabel"
    />

    <!-- Main Content -->
    {{ $slot }}
</div>