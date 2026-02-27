<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add affiliate settings to products
        Schema::table('tbl_products', function (Blueprint $table) {
            $table->decimal('affiliate_commission_rate', 5, 2)->default(10.00)->after('is_digital');
            $table->boolean('affiliate_enabled')->default(true)->after('affiliate_commission_rate');
        });

        // Affiliate links: creator → product promotion relationship
        Schema::create('tbl_affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_id');
            $table->unsignedBigInteger('product_id');
            $table->string('affiliate_code', 20)->unique();
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->integer('click_count')->default(0);
            $table->integer('purchase_count')->default(0);
            $table->integer('total_earnings')->default(0);
            $table->smallInteger('status')->default(1); // 1=active, 2=paused
            $table->timestampsTz();

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->unique(['creator_id', 'product_id']);
            $table->index(['creator_id', 'status']);
            $table->index('affiliate_code');
        });

        // Affiliate earnings: tracks each commission from a sale
        Schema::create('tbl_affiliate_earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_link_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('commission_coins');
            $table->smallInteger('status')->default(1); // 1=paid (instant)
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('affiliate_link_id')->references('id')->on('tbl_affiliate_links')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('tbl_product_orders')->onDelete('set null');
            $table->index(['affiliate_link_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_affiliate_earnings');
        Schema::dropIfExists('tbl_affiliate_links');

        Schema::table('tbl_products', function (Blueprint $table) {
            $table->dropColumn(['affiliate_commission_rate', 'affiliate_enabled']);
        });
    }
};
