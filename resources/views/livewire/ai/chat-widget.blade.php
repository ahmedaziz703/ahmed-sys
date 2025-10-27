@php
use Illuminate\Support\HtmlString;

// Define the formatting function here (consider moving to a helper or component method later)
if (!function_exists('formatChatMessage')) {
    function formatChatMessage(string $content): HtmlString
    {
        // NEW PRE-PROCESSING STEP: Clean up markdown symbols
        // Remove markdown headers (###) 
        $content = preg_replace('/^#+\s+/m', '', $content);
        
        // Remove markdown bold/italic formatting ** and *
        $content = preg_replace('/\*\*(.*?)\*\*/s', '$1', $content);
        $content = preg_replace('/\*(.*?)\*/s', '$1', $content);
        
        // Normalize multiple consecutive newlines to just two (like paragraphs)
        $content = preg_replace('/\n{3,}/', "\n\n", trim($content));

        // Escape HTML to prevent XSS 
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        // Convert newlines to <br>
        $content = nl2br($content);
        
        // Final trim of the entire output
        $content = trim($content);

        return new HtmlString($content);
    }
}
@endphp

<div>
    {{-- Add CSRF token directly in the component instead of using @push --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Global Alpine Store Setup - تم إضافته في بداية الصفحة للعمل --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('chat', {
                isOpen: false,
                toggle() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen) {
                        setTimeout(() => {
                            const chatMessages = document.getElementById('chat-messages');
                            if (chatMessages) {
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                                console.log('تم فتح المحادثة، تم التمرير لأسفل (المتجر العام)');
                            }
                        }, 100);
                    }
                }
            });
        });
    </script>

    {{-- Simple Chat Widget Container --}}
    <div x-data>
        {{-- Chat Button --}}
        <button 
            @click="$store.chat.toggle()"
            class="fixed bottom-5 left-5 flex items-center justify-center w-12 h-12 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg"
        >
            <span x-show="!$store.chat.isOpen">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </span>
            <span x-show="$store.chat.isOpen">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </span>
        </button>

        {{-- Chat Panel --}}
        <div
            x-show="$store.chat.isOpen"
            class="fixed bottom-20 left-4 w-96 max-w-[calc(100vw-2rem)] h-[36rem] bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden flex flex-col z-50"
            @click.away="$store.chat.isOpen = false"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">مساعدك الذكي</h3>
                        <p class="text-xs text-gray-500">كيف يمكنني مساعدتك؟</p>
                    </div>
                </div>
                <div>
                    <button 
                        wire:click="startNewConversation"
                        class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                        title="محادثة جديدة"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                    <!-- Hidden refresh button, to refresh the UI when the page is first loaded -->
                    <button id="autoRefreshButton" wire:click="$refresh" class="hidden"></button>
                </div>
            </div>

            {{-- Messages --}}
            <div id="chat-messages" class="flex-1 overflow-y-auto p-3 space-y-2">
                @forelse($messages as $message)
                    <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] {{ $message->role === 'user' ? 'bg-primary-50 text-primary-900' : 'bg-gray-100 text-gray-900' }} rounded-lg px-3 py-1.5 shadow-sm">
                            <div class="chat-message" id="message-{{ $message->id }}">
                                <?php
                                    // Clean up markdown symbols
                                    $content = $message->content;
                                    
                                    // Clean up headers (### Header)
                                    $content = preg_replace('/^#{1,6}\s+/m', '', $content);
                                    
                                    // Clean up bold (**text**) and italic (*text*)
                                    $content = preg_replace('/\*\*(.*?)\*\*/s', '$1', $content);
                                    $content = preg_replace('/\*(.*?)\*/s', '$1', $content);
                                    
                                    // Safe output
                                    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                                    
                                    // Convert newlines to <br>
                                    $content = nl2br($content);
                                ?>
                                {!! $content !!}
                            </div>
                            <div class="mt-1 text-[10px] {{ $message->role === 'user' ? 'text-primary-400' : 'text-gray-400' }} text-right">
                                {{ $message->created_at->format('H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 text-sm py-10">
                        لا توجد رسائل بعد.
                    </div>
                @endforelse

                {{-- Typing Indicator - shows when we're waiting for the API response --}}
                @if($isTyping)
                    <div class="flex justify-start animate-pulse">
                        <div class="bg-gray-100 text-gray-900 rounded-lg px-4 py-3 shadow-sm border border-gray-200">
                            <div class="flex items-center space-x-3">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                    <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-600">الذكاء الاصطناعي يكتب الرد...</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Input --}}
            <div class="p-3 border-t border-gray-200">
                <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                    <input 
                        type="text" 
                        wire:model="newMessageText"
                        placeholder="اكتب رسالتك..."
                        class="flex-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        @if($isTyping || $isInputDisabled) disabled @endif
                    >
                    <button 
                        type="submit"
                        class="inline-flex items-center justify-center px-2.5 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        @if($isTyping || $isInputDisabled) disabled @endif
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Define global variable for Alpine store to be used on all pages
        window.chatWidget = {
            initialized: false,
            livewireInitialized: false
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            // Set CSRF token
            var token = document.querySelector('meta[name="csrf-token"]');
            if (token && window.livewire) {
                window.livewire.addHeaders({
                    'X-CSRF-TOKEN': token.getAttribute('content')
                });
            }
            
            // Scroll to bottom of messages
            var scrollToBottom = function() {
                var chatMessages = document.getElementById('chat-messages');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            };
            
            // Scroll on page load and after updates
            scrollToBottom();
            
            // Livewire component is ready
            document.addEventListener('livewire:initialized', () => {
                scrollToBottom();
                window.chatWidget.livewireInitialized = true;
                console.log('تم تهيئة Livewire، المكون جاهز');
            });
            
            document.addEventListener('livewire:update', () => {
                scrollToBottom();
            });
            
            // Livewire page navigation event
            document.addEventListener('livewire:navigated', () => {
                // Wait for components to load after page navigation
                setTimeout(() => {
                    scrollToBottom();
                    
                    // Check if Livewire context is ready
                    if (window.Livewire) {
                        window.chatWidget.livewireInitialized = true;
                        console.log('تم التنقل في Livewire، تم تحديث السياق');
                    }
                }, 200);
            });
            
            // مستمع حدث processAIQuery - بطريقة أكثر أماناً
            if (window.Livewire) {
                Livewire.on('processAIQuery', function(data) {
                    // Process response
                    setTimeout(function() {
                        // Check if Livewire and component are ready
                        if (window.Livewire && window.chatWidget.livewireInitialized) {
                            try {
                                // Livewire 3 syntax - correct format: dispatch(eventName, {key: value})
                                // send messageText in object
                                Livewire.dispatch('processAIResponseAsync', { messageText: data.messageText });
                                console.log('تم إرسال استجابة الذكاء الاصطناعي بالتنسيق الصحيح لـ Livewire 3');
                            } catch (e) {
                                console.error('Error dispatching event:', e);
                                // Alternative method - manual Livewire find
                                try {
                                    // Direct component access
                                    const el = document.querySelector('[wire\\:id]');
                                    if (el) {
                                        // Livewire 3 - $wire can be used or direct wire:id access
                                        if (typeof Livewire.find === 'function') {
                                            Livewire.find(el.getAttribute('wire:id')).call('processAIResponse', data.messageText);
                                            console.log('نجح استدعاء الطريقة المباشرة');
                                        } else {
                                            // Completely manual form submission
                                            const form = document.createElement('form');
                                            form.style.display = 'none';
                                            form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                                            <input type="hidden" name="messageText" value="${data.messageText}">`;
                                            document.body.appendChild(form);
                                            form.method = 'POST';
                                            form.action = window.location.href;
                                            form.submit();
                                        }
                                    } else {
                                        console.error('لم يتم العثور على عنصر مكون Livewire');
                                    }
                                } catch (e2) {
                                    console.error('All methods failed:', e2);
                                }
                            }
                        } else {
                            console.warn('لم يتم العثور على مكون Livewire، تم تخطي العملية');
                        }
                    }, 300);
                });
            }
            
            // MutationObserver for new messages
            var observer = new MutationObserver(scrollToBottom);
            var chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                observer.observe(chatMessages, { childList: true, subtree: true });
            }
        });
    </script>

    <style>
        .chat-message {
            font-family: inherit;
            font-size: 14.5px;
            line-height: 1.5;
            white-space: normal;
            word-wrap: break-word;
            color: inherit;
        }
    </style>
    
    <!-- Script for automatic refresh when the page is loaded -->
    <script>
        // Perform necessary actions when the page is loaded
        window.addEventListener('load', function() {
            // Automatic refresh when the page is loaded
            setTimeout(function() {
                document.getElementById('autoRefreshButton')?.click();
            }, 500);
        });
    </script>
</div>
