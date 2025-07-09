<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->text('comment')->nullable();
            $table->string('reason')->nullable();
            $table->string('type')->nullable();
            $table->enum('status', ['processing', 'rejected', 'approved'])->default('processing');
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('refund_request_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_request_id');
            $table->string('image_path');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_request_images');
        Schema::dropIfExists('refund_requests');
    }
}; 