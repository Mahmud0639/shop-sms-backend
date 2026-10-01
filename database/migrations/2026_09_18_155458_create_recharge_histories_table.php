<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('package_name')->nullable();
            $table->integer('sms_amount');
            $table->decimal('price', 10, 2);
            $table->string('payment_method')->nullable(); // bkash, nagad, card, etc.
            $table->string('transaction_id')->unique();
            $table->string('val_id')->nullable(); // SSLCommerz Validation ID
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_histories');
    }
};