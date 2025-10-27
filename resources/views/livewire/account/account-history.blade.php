<x-table.table-layout
    pageTitle="{{ $account->name }} - تاريخ المعاملات"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'جميع الحسابات']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 