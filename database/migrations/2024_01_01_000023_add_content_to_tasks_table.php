<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add new content columns
            $table->json('content')->nullable(); // TipTap JSON content
            $table->json('checklist')->nullable(); // Checklist items
            
            // If assigned_to column already exists, add it
            if (!Schema::hasColumn('tasks', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['content', 'checklist']);
            
            if (Schema::hasColumn('tasks', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
        });
    }
}; 