<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['individual', 'corporate'])->default('corporate');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->enum('source', ['website', 'referral', 'social_media', 'other'])->default('other');
            $table->enum('status', ['new', 'contacted', 'proposal_sent', 'negotiating', 'converted', 'lost'])->default('new');
            $table->timestamp('last_contact_date')->nullable();
            $table->timestamp('next_contact_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_to_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('conversion_reason')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
