<x-table.table-layout 
    pageTitle="إدارة الأدوار"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true, 'icon' => 'fas fa-home'],
        ['label' => 'الأدوار', 'icon' => 'fas fa-user-shield'],
        ['label' => 'قائمة']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>