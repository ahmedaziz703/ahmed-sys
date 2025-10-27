<x-table.table-layout
    pageTitle="القروض"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'القروض']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>