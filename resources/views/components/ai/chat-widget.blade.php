<div 
    x-data="{ 
        isOpen: false,
        messages: [],
        newMessage: '',
        sendMessage() {
            if (this.newMessage.trim() === '') return;
            $wire.sendMessage(this.newMessage);
            this.newMessage = '';
        }
    }"
    class="fixed bottom-4 left-4 items-start md:right-4 md:left-auto md:items-end z-40 flex flex-col"
>
    {{-- Chat Button --}}
    <button 
        @click="isOpen = !isOpen"
        class="flex items-center justify-center w-12 h-12 mb-2 rounded-full bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition-all duration-200"
    >
        <template x-if="!isOpen">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </template>
        <template x-if="isOpen">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </template>
    </button>

    {{-- Chat Panel --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="w-96 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
        @click.away="isOpen = false"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">AI Asistanınız</h3>
                    <p class="text-xs text-gray-500">Size nasıl yardımcı olabilirim?</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button 
                    @click="$wire.startNewConversation()"
                    class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                    title="محادثة جديدة"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <button 
                    @click="$wire.toggleHistory()"
                    class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                    title="Geçmiş Sohbetler"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Container --}}
        <div class="h-96 overflow-y-auto p-4 space-y-4" id="chat-messages">
            @foreach($messages as $message)
                <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] {{ $message->role === 'user' ? 'bg-primary-100 text-primary-900' : 'bg-gray-100 text-gray-900' }} rounded-lg px-4 py-2 shadow-sm">
                        {!! nl2br(e($message->content)) !!}
                        <div class="mt-1 text-xs {{ $message->role === 'user' ? 'text-primary-600' : 'text-gray-500' }}">
                            {{ $message->created_at->format('H:i') }}
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Typing Indicator --}}
            <div x-show="$wire.isTyping" class="flex justify-start">
                <div class="bg-gray-100 rounded-lg px-4 py-2 shadow-sm">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="p-4 border-t border-gray-200">
            <form @submit.prevent="sendMessage" class="flex space-x-2">
                <input 
                    type="text" 
                    x-model="newMessage"
                    placeholder="Mesajınızı yazın..."
                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    @keydown.enter.prevent="sendMessage"
                >
                <button 
                    type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div> 
