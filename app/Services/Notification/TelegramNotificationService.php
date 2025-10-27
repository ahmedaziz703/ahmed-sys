<?php

namespace App\Services\Notification;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramNotificationService
{
    /**
     * @var Api|null Telegram API instance
     */
    protected ?Api $telegram = null;

    /**
     * Send notification via Telegram
     */
    public function send(string $message): bool
    {
        try {
            // Check if Telegram is enabled
            $enabled = Setting::where('group', 'telegram')
                ->where('key', 'telegram_enabled')
                ->first();
                
            if (!$enabled || !filter_var($enabled->value, FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }
            
            // Get token and chat ID
            $token = Setting::where('group', 'telegram')
                ->where('key', 'telegram_bot_token')
                ->first();
                
            $chatId = Setting::where('group', 'telegram')
                ->where('key', 'telegram_chat_id')
                ->first();
                
            if (!$token || !$token->value || !$chatId || !$chatId->value) {
                return false;
            }
            
            // Telegram API instance
            if (!$this->telegram) {
                $this->telegram = new Api($token->value);
            }
            
            // Send message
            $this->telegram->sendMessage([
                'chat_id' => $chatId->value,
                'text' => $message,
                'parse_mode' => 'HTML', 
            ]);
            
            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram hatası: ' . $e->getMessage());
            return false;
        }
    }
} 