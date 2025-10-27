<x-form.form-layout
    :content="null"
    pageTitle="Site Settings"
    backLabel="Back to Settings"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'الاعدادت', 'url' => route('admin.settings.index'), 'wire' => true],
        ['label' => 'الاقساط']
    ]"

>
    {{ $this->form }}
</x-form.form-layout> 