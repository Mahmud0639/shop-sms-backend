<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shop_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->text('message_body');
    $table->date('scheduled_date');
    $table->time('scheduled_time')->default('10:00:00'); // <--- এই কলামটি যোগ করুন
    $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_schedules');
    }
};