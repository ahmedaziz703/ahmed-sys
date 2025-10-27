<x-table.table-layout
    pageTitle="تتبع الديون والمستحقات"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'تتبع الديون والمستحقات']
    ]"
>

    {{ $this->table }}
</x-table.table-layout> 