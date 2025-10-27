<x-table.table-layout
    pageTitle="فئات الإيرادات والمصروفات"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'فئات الإيرادات والمصروفات']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 