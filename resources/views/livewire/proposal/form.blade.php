<x-form.form-layout
    :content="$record"
    pageTitle="{{ $isEdit ? 'تعديل العرض' : 'إنشاء عرض' }}"
    backRoute="{{ route('admin.proposals.templates') }}"
    backLabel="العودة للعروض"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'العروض', 'url' => route('admin.proposals.templates'), 'wire' => true],
        ['label' => $isEdit ? 'تعديل' : 'إنشاء']
    ]"
>
{{ $this->form }}

</x-form.form-layout>
