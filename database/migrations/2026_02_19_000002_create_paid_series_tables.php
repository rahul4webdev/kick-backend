<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_paid_series', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->unsigned();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->integer('price_coins')->default(0);
            $table->integer('video_count')->default(0);
            $table->integer('purchase_count')->default(0);
            $table->bigInteger('total_revenue')->default(0);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('status')->default(1); // 1=pending, 2=approved, 3=rejected
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['is_active', 'status']);
            $table->index(['creator_id', 'is_active']);
        });

        Schema::create('tbl_paid_series_videos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('series_id')->unsigned();
            $table->bigInteger('post_id')->unsigned();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('series_id')->references('id')->on('tbl_paid_series')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->unique(['series_id', 'post_id']);
        });

        Schema::create('tbl_paid_series_purchases', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('series_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->integer('amount_coins')->default(0);
            $table->bigInteger('transaction_id')->nullable();
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();

            $table->foreign('series_id')->references('id')->on('tbl_paid_series')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['series_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_paid_series_purchases');
        Schema::dropIfExists('tbl_paid_series_videos');
        Schema::dropIfExists('tbl_paid_series');
    }
};
