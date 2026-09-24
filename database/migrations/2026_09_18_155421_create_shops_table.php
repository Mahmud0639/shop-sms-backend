<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->string('shop_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('pin');
            
        // Foreign key constrained বাদ দিয়ে শুধু integer রাখা হয়েছে:
    $table->unsignedBigInteger('division_id')->nullable();
    $table->unsignedBigInteger('district_id')->nullable();
    $table->unsignedBigInteger('upazila_id')->nullable();
            $table->text('address')->nullable();
            
            // ব্লকিং ও ওয়ালেট সিস্টেম
            $table->enum('status', ['active', 'blocked', 'suspended'])->default('active');
            $table->text('block_reason')->nullable();
            $table->integer('sms_wallet_balance')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};