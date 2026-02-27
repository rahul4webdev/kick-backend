<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_live_stream_products', function (Blueprint $table) {
            $table->id();
            $table->string('room_id', 100);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('seller_id');
            $table->integer('position')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->integer('units_sold')->default(0);
            $table->integer('revenue_coins')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['room_id', 'product_id']);
            $table->index(['room_id', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_live_stream_products');
    }
};
