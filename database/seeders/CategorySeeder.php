<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        // Income Categories
        $incomeCategories = [
            ['name' => 'راتب', 'color' => '#22c55e'],
            ['name' => 'عمل حر', 'color' => '#3b82f6'],
            ['name' => 'إيرادات الاستثمار', 'color' => '#f59e0b'],
            ['name' => 'إيراد الإيجار', 'color' => '#8b5cf6'],
            ['name' => 'إيرادات أخرى', 'color' => '#64748b'],
        ];

        foreach ($incomeCategories as $category) {
            Category::create([
                'user_id' => $user->id,
                'type' => 'income',
                'name' => $category['name'],
                'color' => $category['color'],
                'status' => true,
            ]);
        }

        // Expense Categories
        $expenseCategories = [
            ['name' => 'إيجار', 'color' => '#ef4444'],
            ['name' => 'سوق', 'color' => '#f97316'],
            ['name' => 'فواتير', 'color' => '#06b6d4'],
            ['name' => 'مواصلات', 'color' => '#6366f1'],
            ['name' => 'صحة', 'color' => '#ec4899'],
            ['name' => 'تعليم', 'color' => '#14b8a6'],
            ['name' => 'ترفيه', 'color' => '#f43f5e'],
            ['name' => 'مصروفات أخرى', 'color' => '#64748b'],
        ];

        foreach ($expenseCategories as $category) {
            Category::create([
                'user_id' => $user->id,
                'type' => 'expense',
                'name' => $category['name'],
                'color' => $category['color'],
                'status' => true,
            ]);
        }
    }
} 