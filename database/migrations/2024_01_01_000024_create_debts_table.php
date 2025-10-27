<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['loan_payment', 'debt_payment']);
            $table->decimal('amount', 15, 5);
            $table->string('currency', 10)->default('TRY');
            $table->decimal('buy_price', 15, 2)->nullable(); // Buy price
            $table->decimal('sell_price', 15, 2)->nullable(); // Sell price
            $table->decimal('profit_loss', 15, 2)->nullable(); // Profit/Loss
            $table->text('description')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
}; 