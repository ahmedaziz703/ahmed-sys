<x-form.form-layout
    :content="$transaction"
    pageTitle="{{ $transaction ? 'تعديل المعاملة' : 'معاملة جديدة' }}"
    backRoute="{{ route('admin.transactions.index') }}"
    backLabel="العودة للمعاملات"
    :backgroundCard="false"
    :isTransaction="true"
    :breadcrumbs="[
        ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'المعاملات', 'url' => route('admin.transactions.index'), 'wire' => true],
        ['label' => $transaction ? 'تعديل المعاملة' : 'معاملة جديدة', 'url' => '', 'wire' => true],
    ]"
>
    {{ $this->form }}
</x-form.form-layout> 