<x-table.table-layout
    pageTitle="المشاريع"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'المشاريع']
    ]"
>
{{ $this->table }}
</x-table.table-layout> 