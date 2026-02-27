<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_portfolios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->string('theme', 50)->default('default');
            $table->jsonb('custom_colors')->nullable();
            $table->string('headline', 200)->nullable();
            $table->text('bio_override')->nullable();
            $table->jsonb('featured_post_ids')->default('[]');
            $table->boolean('show_products')->default(true);
            $table->boolean('show_links')->default(true);
            $table->boolean('show_subscription_cta')->default(true);
            $table->integer('view_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
        });

        Schema::create('tbl_portfolio_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('portfolio_id');
            $table->string('section_type', 50);
            $table->string('title', 200)->nullable();
            $table->text('content')->nullable();
            $table->jsonb('data')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->foreign('portfolio_id')->references('id')->on('tbl_portfolios')->onDelete('cascade');
            $table->index(['portfolio_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_portfolio_sections');
        Schema::dropIfExists('tbl_portfolios');
    }
};
