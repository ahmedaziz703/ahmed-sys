<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('source_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer', 'payment', 'loan_payment', 'debt_payment', 'atm_deposit', 'atm_withdraw'])->default('expense');
            $table->decimal('amount', 15, 5);
            $table->string('currency', 10)->default('TRY');
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->decimal('try_equivalent', 15, 2)->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable()->default(0); // Transfer masrafı için
            $table->date('date');
            $table->enum('payment_method', ['cash','bank','credit_card','crypto','virtual_pos'])->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('installments')->nullable();
            $table->unsignedTinyInteger('remaining_installments')->nullable();
            $table->decimal('monthly_amount', 15, 2)->nullable();
            $table->date('next_payment_date')->nullable();
            $table->boolean('is_subscription')->default(false);
            $table->enum('subscription_period', ['daily', 'weekly', 'monthly', 'quarterly', 'biannually', 'annually'])->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->boolean('is_taxable')->default(false);
            $table->integer('tax_rate')->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->boolean('has_withholding')->default(false);
            $table->integer('withholding_rate')->nullable();
            $table->decimal('withholding_amount', 12, 2)->nullable();
            $table->foreignId('reference_id')->nullable()->references('id')->on('transactions')->nullOnDelete();
            $table->string('status')->default('completed');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};