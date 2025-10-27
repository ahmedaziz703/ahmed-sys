<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Notification\PaymentNotificationService;
use Illuminate\Console\Command;

class SendUpcomingPaymentNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:upcoming-payments {days=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Yaklaşan ödemeler için Telegram bildirimleri gönderir';

    /**
     * Execute the console command.
     */
    public function handle(PaymentNotificationService $notificationService): int
    {
        // Check if Telegram notifications are active
        $telegramEnabled = Setting::where('group', 'telegram')
            ->where('key', 'telegram_enabled')
            ->first();
            
        // Boolean and string 'true' flexible control
        if (!$telegramEnabled || !filter_var($telegramEnabled->value, FILTER_VALIDATE_BOOLEAN)) {
            $this->info('Telegram bildirimleri devre dışı.');
            return 0;
        }
        
        // Token and chat ID control
        $botToken = Setting::where('group', 'telegram')
            ->where('key', 'telegram_bot_token')
            ->first();
            
        $chatId = Setting::where('group', 'telegram')
            ->where('key', 'telegram_chat_id')
            ->first();
            
        if (!$botToken || !$botToken->value || !$chatId || !$chatId->value) {
            $this->error('Telegram ayarları eksik (bot token veya chat ID).');
            return 1;
        }
        
        $days = (int) $this->argument('days');
        
        $this->info("Bildirimler gönderiliyor ({$days} gün)...");
        
        $notificationService->sendUpcomingPaymentNotifications($days);
        
        $this->info('Bildirimler gönderildi.');
        
        return 0;
    }
} 