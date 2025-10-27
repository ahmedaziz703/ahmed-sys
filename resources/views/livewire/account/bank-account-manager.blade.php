<x-table.table-layout
    pageTitle="حسابات البنك"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'حسابات البنك']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 