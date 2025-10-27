<?php

namespace App\Livewire\Ai;

use App\Models\AIConversation;
use App\Models\AIMessage;
use App\Services\AI\Contracts\AIAssistantInterface;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Chat Widget Component
 * 
 * This component provides a chat interface for AI conversations.
 * Features:
 * - AI conversation management
 * - Message sending and receiving
 * - Typing indicator
 * - Input handling
 * - Conversation state management
 * - Message content management
 * - Conversation state management
 * 
 * @package App\Livewire\AI
 */

class ChatWidget extends Component
{
    // Basic properties
    public ?AIConversation $conversation = null;
    public bool $isTyping = false;
    public string $newMessageText = '';
    public $isInputDisabled = false;
    public ?string $currentTypingResponse = null;
    public ?int $typingMessageId = null;
    
    // Define the protected $queryString array to prevent query string parameters
    protected $queryString = [];
    
    // Define Livewire listeners for events
    protected $listeners = [
        'refreshComponent' => '$refresh',
        'processAIResponseAsync' => 'processAIResponse'
    ];
    
    // Page load
    public function mount()
    {
        $this->getOrCreateConversation();
    }
    
    // Get or create active conversation
    protected function getOrCreateConversation()
    {
        $this->conversation = auth()->user()->aiConversations()
            ->where('is_active', true)
            ->first();

        if (!$this->conversation) {
            $this->conversation = auth()->user()->aiConversations()->create([
                'title' => 'محادثة جديدة',
                'is_active' => true
            ]);
        }
    }

    // Send message
    public function sendMessage()
    {
        // Empty message check
        if (empty(trim($this->newMessageText))) {
            return;
        }

        try {
            // Store message text before clearing
            $messageText = $this->newMessageText;
            
            // Clear input immediately
            $this->newMessageText = '';
            
            // Disable input while waiting for response
            $this->isInputDisabled = true;
            
            // Save user message
            $this->conversation->messages()->create([
                'role' => 'user',
                'content' => $messageText
            ]);
            
            // Set AI typing state
            $this->isTyping = true;
            
            // Refresh the component, so the user message is shown and the input is disabled/cleared
            $this->dispatch('chatUpdated');
            
            // AI response processing delayed call - for better UI experience
            // This JavaScript function is used to show the message on the front end and then make the API call
            // then the API call is separated
            $this->dispatch('processAIQuery', messageText: $messageText);
            
        } catch (\Exception $e) {
            Log::error('Message Sending Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-enable input
            $this->isInputDisabled = false;
            $this->isTyping = false;
            
            $this->dispatch('$refresh');
        }
    }
    
    // Process AI response
    public function processAIResponse($messageText)
    {
        try {
            // Load the database schema and analysis services
            $databaseSchemaService = app(\App\Services\AI\DatabaseSchemaService::class);
            $sqlQueryService = app(\App\Services\AI\SqlQueryService::class);
            $aiAssistant = app(AIAssistantInterface::class);
            
            // Direct AI response without SQL generation
            $response = $aiAssistant->query(
                auth()->user(),
                $messageText,
                (string) $this->conversation->id
            );
            
            // Response check
            if (empty(trim($response))) {
                $response = "آسف، لم يتم إنشاء الرد. يرجى المحاولة مرة أخرى.";
                Log::warning('كان رد API فارغاً، تم استخدام الرسالة الافتراضية');
            }
            
            // Save the response to the database
            $message = $this->conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response
            ]);
            
            // Close the writing state
            $this->isTyping = false;
            
            // Update the UI - show the message
            $this->dispatch('$refresh');
            
            // Input'u تفعيل et
            $this->isInputDisabled = false;
            
        } catch (\Exception $e) {
            Log::error('AI Response Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $this->conversation->id ?? 'unknown'
            ]);
            
            // Send the error message
            if ($this->conversation) {
                $errorMessage = 'آسف، لا أستطيع الرد حالياً. يرجى المحاولة مرة أخرى لاحقاً.';
                
                $this->conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $errorMessage
                ]);
                
                // Reset the UI state
                $this->isTyping = false;
                $this->isInputDisabled = false;
                
                // Update the UI
                $this->dispatch('$refresh');
            }
        }
    }
    
    // Reset the chat UI state and final UI update
    public function resetUIState()
    {
        $this->isTyping = false;
        $this->isInputDisabled = false;
        $this->currentTypingResponse = null;
        $this->typingMessageId = null;
        
        // Final UI update
        $this->dispatch('$refresh');
    }
    
    /**
     * Get the message content - for JavaScript animation
     */
    public function getMessageContent($messageId)
    {
        try {
            // Get the full response from the dispatch event
            // Return the fullResponse value from the frontend
            
            Log::info('getMessageContent called for message', ['id' => $messageId]);
            $message = AIMessage::find($messageId);
            
            if (!$message) {
                Log::warning('Message not found', ['id' => $messageId]);
                return null;
            }
            
            // If the content is empty, use the data sent by JavaScript
            if (empty($message->content)) {
                // Return the fullResponse parameter sent by JavaScript
                $response = session("ai_response_{$messageId}");
                Log::info('Using session response for animation', ['id' => $messageId, 'found' => !empty($response)]);
                
                if (!empty($response)) {
                    // Save the response to the database
                    $message->update(['content' => $response]);
                    return $response;
                }
            }
            
            // If the message content is not empty, return it directly
            return $message->content;
            
        } catch (\Exception $e) {
            Log::error('Message content fetch error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);
            return null;
        }
    }
    
    /**
     * Update the message content during animation
     */
    public function updateMessageContent($messageId, $content)
    {
        try {
            // Find and update the message
            $message = AIMessage::find($messageId);
            if ($message) {
                $message->update(['content' => $content]);
                Log::info('Message content updated', ['id' => $messageId, 'length' => strlen($content)]);
            }
        } catch (\Exception $e) {
            Log::error('Message update error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);
        }
    }
    
    /**
     * Save the response from JavaScript to the session
     */
    public function storeResponseInSession($messageId, $fullResponse)
    {
        session(["ai_response_{$messageId}" => $fullResponse]);
        Log::info('Response stored in session', ['id' => $messageId]);
        return true;
    }

    // Start a new conversation
    public function startNewConversation()
    {
        // Make the current conversation inactive
        if ($this->conversation) {
            $this->conversation->update(['is_active' => false]);
        }
        
        // Create a new conversation
        $this->conversation = auth()->user()->aiConversations()->create([
            'title' => 'جديد Sohbet',
            'is_active' => true
        ]);
        
        // Reset the states
        $this->isTyping = false;
        $this->isInputDisabled = false;
        $this->currentTypingResponse = null;
        $this->typingMessageId = null;
        
        // Force refresh
        $this->dispatch('$refresh');
    }
    
    // Get all messages
    public function getMessages()
    {
        if (!$this->conversation) {
            return collect();
        }
        
        return $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    // Render the component view
    public function render()
    {
        return view('livewire.ai.chat-widget', [
            'messages' => $this->getMessages()
        ]);
    }
} 