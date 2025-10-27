<x-table.table-layout
    pageTitle="العملاء المحتملين"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'العملاء المحتملين']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 