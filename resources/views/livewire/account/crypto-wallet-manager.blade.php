<x-table.table-layout
    pageTitle="محفظة العملات المشفرة"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'محفظة العملات المشفرة']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 