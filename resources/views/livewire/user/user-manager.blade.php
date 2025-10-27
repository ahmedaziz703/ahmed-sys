<x-table.table-layout 
    pageTitle="إدارة المستخدمين"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true, 'icon' => 'fas fa-home'],
        ['label' => 'المستخدمون', 'icon' => 'fas fa-user'],
        ['label' => 'قائمة']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 