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
        $tables = [
            'products',
            'orders',
            'order_products',
            'users',
            'categories',
            'addresses',
            'sizes',
            'product_reviews',
            'product_review_images',
            'product_media',
            'images',
        ];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'products',
            'orders',
            'order_products',
            'users',
            'categories',
            'addresses',
            'sizes',
            'product_reviews',
            'product_review_images',
            'product_media',
            'images',
        ];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}; 