<x-table.table-layout
    :pageTitle="$account->name . ' - المعاملات'"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'الحسابات', 'url' => route('admin.accounts.index'), 'wire' => true],
        ['label' => 'بطاقات الائتمان', 'url' => route('admin.accounts.credit-cards'), 'wire' => true],
        ['label' => $account->name]
    ]"
>
    <div>
        {{ $this->table }}
    </div>
</x-table.table-layout> 