<div class="grid gap-6 mb-8 md:grid-cols-3">
    @foreach($this->getStats() as $stat)
        <div class="relative overflow-hidden bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="inline-flex items-center justify-center p-3 rounded-full 
                        {{ match($stat['color']) {
                            'info' => 'bg-blue-50 text-blue-500',
                            'danger' => 'bg-red-50 text-red-500',
                            'warning' => 'bg-yellow-50 text-yellow-500',
                            'primary' => 'bg-indigo-50 text-indigo-500',
                            'success' => 'bg-green-50 text-green-500',
                        } }}">
                        <x-dynamic-component :component="$stat['icon']" class="w-6 h-6" />
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 uppercase">
                            {{ $stat['label'] }}
                        </p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-1">
                            {{ $stat['value'] }}
                        </h3>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-0 left-0 w-full h-1 
                {{ match($stat['color']) {
                    'info' => 'bg-blue-500',
                    'danger' => 'bg-red-500',
                    'warning' => 'bg-yellow-500',
                    'primary' => 'bg-indigo-500',
                    'success' => 'bg-green-500',
                } }}">
            </div>
        </div>
    @endforeach
</div> 