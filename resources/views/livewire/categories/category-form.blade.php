<x-form.form-layout
    :content="$category"
    pageTitle="{{ $category->exists ? 'تعديل فئة الإيرادات' : 'إضافة فئة إيرادات' }}"
    backRoute="{{ route('admin.categories') }}"
    backLabel="العودة لفئات الإيرادات"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'فئات الإيرادات', 'url' => route('admin.categories'), 'wire' => true],
    ]"
>
{{ $this->form }}
</x-form.form-layout> 