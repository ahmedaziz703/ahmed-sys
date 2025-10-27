<x-table.table-layout
    pageTitle="خطط الادخار"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'خطط الادخار']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 