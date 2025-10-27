<x-table.table-layout
    pageTitle="الودائع"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'الودائع']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 