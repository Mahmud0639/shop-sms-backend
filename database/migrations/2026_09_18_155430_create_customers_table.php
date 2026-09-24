<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            
            // শপ রিলেশন
            $table->unsignedBigInteger('shop_id');
            
            // কাস্টমার বেসিক তথ্য
            $table->string('name');
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            
            // হিসাব সংক্রান্ত ফিল্ড
            $table->decimal('opening_due', 10, 2)->default(0.00);
            $table->decimal('credit_limit', 10, 2)->nullable(); // বাকির সীমা
            
            // পরিচয় ও রেফারেন্স তথ্য
            $table->string('nid_number')->nullable();
            $table->string('reference_name')->nullable();
            $table->string('reference_phone')->nullable();
            $table->text('note')->nullable();
            
            // স্ট্যাটাস
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->timestamps();

            // একই শপে একই ফোন নম্বর ডুপ্লিকেট না হওয়ার ইনডেক্সিং (অপশনাল)
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};