@props([
    'pageTitle' => '',
    'breadcrumbs' => [],
    'backRoute' => null,
    'backLabel' => null,
    'createRoute' => null,
    'createLabel' => null,
])

<div class="mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="mt-2 text-xl font-semibold text-gray-700 dark:text-white">
                {{ $pageTitle }}
            </h1>
            
            @if(count($breadcrumbs))
                <nav class="mt-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center rtl:space-x-reverse text-sm">
                        <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd"></path>
                        </svg>
                        @foreach($breadcrumbs as $breadcrumb)
                            <li class="{{ !$loop->first ? 'flex items-center' : 'inline-flex items-center' }}">
                                @if(!$loop->first)
                                    <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                    </svg>
                                @endif
                                
                                @if(isset($breadcrumb['url']) && !$loop->last)
                                    <a href="{{ $breadcrumb['url'] }}" 
                                       class="text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white"
                                       @if(isset($breadcrumb['wire'])) wire:navigate @endif
                                    >
                                        {{ $breadcrumb['label'] }}
                                    </a>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ $breadcrumb['label'] }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
        </div>

        <div class="mt-3 sm:mt-0">
            @if($backRoute)
                <a 
                    href="{{ $backRoute }}" 
                    class="inline-flex items-center text-gray-600 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white"
                    wire:navigate
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ $backLabel ?? 'Back' }}
                </a>
            @endif

            @if($createRoute)
                <a 
                    href="{{ $createRoute }}" 
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    wire:navigate
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $createLabel ?? 'Create' }}
                </a>
            @endif
        </div>
    </div>
</div>