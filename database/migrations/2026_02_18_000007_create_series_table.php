<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('genre', 100)->nullable();
            $table->string('language', 50)->nullable();
            $table->integer('episode_count')->default(0);
            $table->bigInteger('total_views')->default(0);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('status')->default(1); // 1=pending, 2=approved, 3=rejected
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['is_active', 'status'], 'idx_series_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_series');
    }
};
