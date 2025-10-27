<x-table.table-layout
    pageTitle="العملاء"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'العملاء']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 