<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cart items table
        Schema::create('tbl_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->unique(['user_id', 'product_id']);
            $table->index('user_id');
        });

        // Shipping addresses table
        Schema::create('tbl_shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 200);
            $table->string('phone', 50)->nullable();
            $table->text('address_line1');
            $table->text('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20);
            $table->string('country', 100)->default('India');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'is_default']);
        });

        // Order items table (for multi-product orders)
        Schema::create('tbl_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->integer('price_coins');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('tbl_product_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_order_items');
        Schema::dropIfExists('tbl_shipping_addresses');
        Schema::dropIfExists('tbl_cart_items');
    }
};
