<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->after('category_id')->nullable();
            $table->string('slug')->after('brand')->unique();
            $table->string('tagline')->after('slug')->nullable();
            $table->text('heart_notes')->after('tagline')->nullable();
            $table->text('top_notes')->after('heart_notes')->nullable();
            $table->text('base_notes')->after('top_notes')->nullable();
            $table->boolean('status')->after('base_notes')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand',
                'slug',
                'tagline',
                'heart_notes',
                'top_notes',
                'base_notes',
                'status'
            ]);
        });
    }
};
