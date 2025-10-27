<x-table.table-layout
    pageTitle="الموردين"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'الموردين']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>