<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('sms_packages', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // যেমন: Basic, Standard
        $table->integer('sms_amount'); // যেমন: 100, 500
        $table->decimal('price', 8, 2); // যেমন: 50.00
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_packages');
    }
};
