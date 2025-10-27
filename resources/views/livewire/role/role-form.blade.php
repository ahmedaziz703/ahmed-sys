<x-form.form-layout
    :content="$role"
    :pageTitle="$isEdit ? 'تعديل الدور: ' . $role->name : 'إنشاء دور جديد'"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'الأدوار', 'url' => route('admin.roles.index'), 'wire' => true],
        ['label' => $isEdit ? 'تعديل' : 'إنشاء']
    ]"
    backRoute="{{ route('admin.roles.index') }}"
    backLabel="العودة للأدوار"
>
    {{ $this->form }}
</x-form.form-layout>