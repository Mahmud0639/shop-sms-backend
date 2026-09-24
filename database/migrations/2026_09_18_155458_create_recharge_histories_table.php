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
            $table->string('package_name');
            $table->integer('sms_amount');
            $table->decimal('price', 10, 2);
            $table->enum('payment_method', ['bkash', 'nagad', 'rocket']);
            $table->string('transaction_id');
            $table->enum('status', ['success', 'pending', 'failed'])->default('success');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_histories');
    }
};