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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('no_choose')->nullable()->after('type');
            $table->unsignedBigInteger('choose_category')->nullable()->after('no_choose');
            $table->foreign('choose_category')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['choose_category']);
            $table->dropColumn(['no_choose', 'choose_category']);
        });
    }
}; 