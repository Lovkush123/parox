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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., SAVE10
            $table->string('type')->default('percentage'); // percentage or fixed
            $table->decimal('value', 8, 2); // discount value
            $table->decimal('min_purchase', 8, 2)->nullable(); // optional minimum order amount
            $table->decimal('max_discount', 8, 2)->nullable(); // optional maximum discount cap
            $table->date('start_date')->nullable(); // coupon validity start
            $table->date('end_date')->nullable(); // coupon validity end
            $table->boolean('is_active')->default(true); // coupon status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
