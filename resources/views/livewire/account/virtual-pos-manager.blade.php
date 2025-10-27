<x-table.table-layout
    pageTitle="نقاط البيع الافتراضية"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'نقاط البيع الافتراضية']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 