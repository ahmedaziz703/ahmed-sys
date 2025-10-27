<x-table.table-layout
    pageTitle="بطاقات الائتمان"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'بطاقات الائتمان']
    ]"
>

    <livewire:credit-card.widgets.credit-card-stats-widget />
    {{ $this->table }}
</x-table.table-layout> 