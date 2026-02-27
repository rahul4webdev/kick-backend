<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product Categories
        Schema::create('tbl_product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('icon', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Products
        Schema::create('tbl_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('price_coins');
            $table->jsonb('images')->nullable(); // array of image paths
            $table->integer('stock')->default(-1); // -1 = unlimited
            $table->integer('sold_count')->default(0);
            $table->integer('rating_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('view_count')->default(0);
            $table->smallInteger('status')->default(1); // 1=pending, 2=approved, 3=rejected
            $table->boolean('is_active')->default(true);
            $table->boolean('is_digital')->default(false); // digital vs physical
            $table->string('digital_file', 500)->nullable(); // path for digital products
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('tbl_product_categories')->onDelete('set null');
            $table->index(['seller_id', 'is_active']);
            $table->index(['category_id', 'is_active', 'status']);
            $table->index(['status', 'is_active', 'created_at']);
        });

        // Product Orders / Purchases
        Schema::create('tbl_product_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('seller_id');
            $table->integer('quantity')->default(1);
            $table->integer('total_coins');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->smallInteger('status')->default(0); // 0=pending, 1=confirmed, 2=shipped, 3=delivered, 4=cancelled, 5=refunded
            $table->text('shipping_address')->nullable();
            $table->string('tracking_number', 200)->nullable();
            $table->text('buyer_note')->nullable();
            $table->text('seller_note')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['buyer_id', 'created_at']);
            $table->index(['seller_id', 'status']);
        });

        // Product Reviews
        Schema::create('tbl_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->smallInteger('rating'); // 1-5
            $table->text('review_text')->nullable();
            $table->jsonb('photos')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_product_reviews');
        Schema::dropIfExists('tbl_product_orders');
        Schema::dropIfExists('tbl_products');
        Schema::dropIfExists('tbl_product_categories');
    }
};
