<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sizes', function (Blueprint $table) {
            $table->integer('mrp')->after('product_id');
            $table->integer('selling')->after('mrp');
            $table->integer('cod')->after('mrp'); // Cash on Delivery
            $table->integer('total_stock')->after('cod')->default(0);
            $table->string('stock_status')->after('total_stock')->default('in_stock'); // or use enum
            $table->decimal('length', 8, 2)->after('stock_status')->nullable();
            $table->decimal('width', 8, 2)->after('length')->nullable();
            $table->decimal('height', 8, 2)->after('width')->nullable();
            $table->decimal('weight', 8, 2)->after('height')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sizes', function (Blueprint $table) {
            $table->dropColumn([
                'mrp',
                'selling',
                'price',
                'cod',
                'total_stock',
                'stock_status',
                'length',
                'width',
                'height',
                'weight'
            ]);
        });
    }
};
