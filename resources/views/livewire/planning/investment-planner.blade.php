<x-table.table-layout
    pageTitle="خطط الاستثمار"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'خطط الاستثمار']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 