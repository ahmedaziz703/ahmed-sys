<x-table.table-layout
        pageTitle="العروض"
        :breadcrumbs="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
            ['label' => 'العروض'],
        ]"
    >
        {{ $this->table }}
</x-table.table-layout>