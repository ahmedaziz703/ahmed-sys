<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer Credentials Table
 * 
 * This table manages sensitive information for customers (domains, hosting, servers, etc.).
 * Features:
 * - Store sensitive information encrypted
 * - Add/edit/delete information
 * - Track information history
 * - User-based authorization
 * 
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Creates the table
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('customer_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('value'); 
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Deletes the table
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_credentials');
    }
}; 