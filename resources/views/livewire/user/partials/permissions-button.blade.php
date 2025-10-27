<div>
    <button 
        type="button"
        wire:click="showPermissions({{ $roleId }})"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-100 border border-transparent rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    >
        عرض الصلاحيات ({{ $count }})
    </button>
</div> 