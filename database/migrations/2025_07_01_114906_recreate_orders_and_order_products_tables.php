<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateOrdersAndOrderProductsTables extends Migration
{
    public function up()
    {
        // Drop existing tables if they exist
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('order_list');
        Schema::dropIfExists('orders');

        // Create orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('unique_order_id')->unique();
            $table->string('order_status')->nullable();       // e.g., placed, processing, completed
            $table->string('delivery_status')->nullable();    // e.g., pending, shipped, delivered
            $table->string('payment_status')->nullable();     // e.g., paid, unpaid, failed
            $table->string('payment_response_id')->nullable(); // Payment gateway ID
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->enum('payment_type', ['prepaid', 'cod'])->nullable();
            $table->timestamps();
        });

        // Create order_products table
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('size_id')->nullable()->constrained('sizes')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_list');
    }
}
