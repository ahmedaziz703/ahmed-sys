<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // Cache facade'ını ekle

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        $settings = [
            // Site Settings
            ['group' => 'site', 'key' => 'site_title', 'value' => 'Ahmed Aziz | Ahmed Aziz CRM', 'type' => 'text'],
            ['group' => 'site', 'key' => 'site_logo', 'value' => 'site/logo.svg', 'type' => 'text'], // أو يمكن استخدام نوع 'file' إذا كان مدعومًا
            ['group' => 'site', 'key' => 'site_favicon', 'value' => 'site/favicon.svg', 'type' => 'text'], // أو يمكن استخدام نوع 'file'

            // Notification Settings
            ['group' => 'notification', 'key' => 'notify_credit_card_statement', 'value' => false, 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_loan_payment', 'value' => false, 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_recurring_payment', 'value' => false, 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_debt_receivable', 'value' => false, 'type' => 'boolean'],

            // Telegram Settings
            ['group' => 'telegram', 'key' => 'telegram_enabled', 'value' => false, 'type' => 'boolean'],
            ['group' => 'telegram', 'key' => 'telegram_bot_token', 'value' => '', 'type' => 'text'],
            ['group' => 'telegram', 'key' => 'telegram_chat_id', 'value' => '', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            // If setting with same group and key exists, update, otherwise create
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }

        // Site settings cache update
        try {
            $siteSettings = Setting::where('group', 'site')
                ->pluck('value', 'key') // 'key' => 'value' format
                ->toArray();

            if (!empty($siteSettings)) {
                Cache::put('site_settings', $siteSettings);
                $this->command->info('تم تحديث ذاكرة التخزين المؤقت لإعدادات الموقع.');
            }
        } catch (\Exception $e) {
             $this->command->error('حدث خطأ أثناء تحديث ذاكرة التخزين المؤقت لإعدادات الموقع: ' . $e->getMessage());
        }
    }
}