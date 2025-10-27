<x-table.table-layout
    :pageTitle="$creditCard->card_name . ' - المعاملات'"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'بطاقات الائتمان', 'url' => route('admin.credit-cards.index'), 'wire' => true],
        ['label' => $creditCard->card_name]
    ]"
>
    <div>
        {{ $this->table }}
    </div>
</x-table.table-layout>
