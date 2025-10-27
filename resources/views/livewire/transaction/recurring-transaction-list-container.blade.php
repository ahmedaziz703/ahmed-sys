<x-table.table-layout
    pageTitle="المعاملات المتكررة"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'المعاملات']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 
