<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderListTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_list', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('order_id');
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('total', 10, 2);
            
            $table->string('response_id')->nullable();
            $table->string('order_status')->default('pending');
            $table->string('tracking_id')->nullable();
            $table->string('payment_type')->nullable();

            $table->timestamps();

            // Optional foreign keys if related tables exist
            // $table->foreign('order_product_id')->references('id')->on('order_product')->onDelete('cascade');
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_list');
    }
}
