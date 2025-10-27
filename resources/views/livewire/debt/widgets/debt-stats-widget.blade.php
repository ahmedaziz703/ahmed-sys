<div class="grid gap-6 mb-8  grid-cols-2 md:grid-cols-5">
    @foreach($this->getStats() as $stat)
        <div class="relative overflow-hidden bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow 
            {{ match($stat['color']) {
                'info' => 'border-blue-500',
                'success' => 'border-green-500',
                'danger' => 'border-red-500',
                'warning' => 'border-yellow-500',
                'primary' => 'border-indigo-500',
            } }}">
            <div class="p-4">
                <div class="flex items-center space-x-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full 
                        {{ match($stat['color']) {
                            'info' => 'bg-blue-100 text-blue-600',
                            'success' => 'bg-green-100 text-green-600',
                            'danger' => 'bg-red-100 text-red-600',
                            'warning' => 'bg-yellow-100 text-yellow-600',
                            'primary' => 'bg-indigo-100 text-indigo-600',
                        } }}">
                        <x-dynamic-component :component="$stat['icon']" class="w-6 h-6" />
                    </div>
                    <div class="flex-1 text-left">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-normal" title="{{ $stat['label'] }}">
                            {{ $stat['label'] }}
                        </p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-1 truncate">
                            {{ $stat['value'] }}
                        </h3>
                    </div>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 w-full h-1 
                {{ match($stat['color']) {
                    'info' => 'bg-blue-500',
                    'success' => 'bg-green-500',
                    'danger' => 'bg-red-500',
                    'warning' => 'bg-yellow-500',
                    'primary' => 'bg-indigo-500',
                } }}">
            </div>
        </div>
    @endforeach
</div>