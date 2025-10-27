<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Debt;
use App\Services\Debt\Contracts\DebtServiceInterface;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Every 30 minutes update the demo data
        if (config('app.app_demo_mode')) {
            $schedule->command('demo:refresh')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        }

        $schedule->call(function () {
            Debt::where('due_date', '<', now()->startOfDay())
                ->where('status', 'pending')
                ->each(function (Debt $debt) {
                    $debt->update(['status' => 'overdue']);
                });
        })->daily();

        // Send Telegram notifications for upcoming payments
        $schedule->command('notifications:upcoming-payments')
            ->dailyAt('08:00')
            ->when(function () {
                $enabled = \App\Models\Setting::where('group', 'telegram')
                    ->where('key', 'telegram_enabled')
                    ->first();
                return $enabled && filter_var($enabled->value, FILTER_VALIDATE_BOOLEAN);
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 