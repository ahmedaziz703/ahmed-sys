<x-table.table-layout
    pageTitle="مجموعات العملاء"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'مجموعات العملاء', 'url' => route('admin.customers.index'), 'wire' => true],
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 