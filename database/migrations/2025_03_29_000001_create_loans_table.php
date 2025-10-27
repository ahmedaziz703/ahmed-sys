<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('loan_type');
            $table->decimal('amount', 15, 5);
            $table->decimal('monthly_payment', 15, 5);
            $table->integer('installments');
            $table->integer('remaining_installments');
            $table->date('start_date');
            $table->date('next_payment_date');
            $table->date('due_date')->nullable();
            $table->decimal('remaining_amount', 15, 5)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
