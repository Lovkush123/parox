<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_review_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_review_id');
            $table->string('image_path');
            $table->timestamps();

            $table->foreign('product_review_id')->references('id')->on('product_reviews')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_review_images');
    }
}; 