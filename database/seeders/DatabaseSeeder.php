<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'phone' => '+905555555555',
            'password' => 'admin123',
            'has_commission' => false,
            'commission_rate' => 0,
            'status' => true,
            'remember_token' => Str::random(10),
        ]);

        User::factory()->create([
            'name' => 'تقارير المستخدم',
            'email' => 'test@test.com',
            'phone' => '+905555555554',
            'password' => 'testtest',
            'has_commission' => true,
            'commission_rate' => 15,
            'status' => true,
            'remember_token' => Str::random(10),
        ]);

        $this->call([
            PermissionSeeder::class,
            AdminUserSeeder::class,
            SettingSeeder::class,
            //DemoDataSeeder::class,
        ]);
    }
}