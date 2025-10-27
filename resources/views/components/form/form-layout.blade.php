@props([
    'content' => null,
    'backRoute' => null,
    'backLabel' => null,
    'pageTitle' => '',
    'breadcrumbs' => [],
    'backgroundCard' => true,
    'languageNotice' => null,
    'isTransaction' => false,
])

<div>
    <x-layouts.page-header 
        :pageTitle="$pageTitle"
        :breadcrumbs="$breadcrumbs"
        :backRoute="$backRoute"
        :backLabel="$backLabel"
        :backgroundCard="$backgroundCard"
        :isTransaction="$isTransaction"
    />

    <!-- Language Notice -->
    @if($languageNotice)
        {!! $languageNotice !!}
    @endif

    <!-- Main Content -->
    <div class="@if($backgroundCard) bg-white dark:bg-gray-800 @endif shadow-sm rounded-lg">
        <div class="@if($backgroundCard) p-6 @endif">
            <form wire:submit="save">
                {{ $slot }}
                
                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 mt-6 @if($isTransaction) pt-12 @endif">
                    <button
                        type="button"
                        wire:click="cancel"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        {{ $content ? 'تحديث' : 'إنشاء' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    