<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_story_highlights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->string('cover_image', 999)->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('item_count')->default(0);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'sort_order']);
        });

        Schema::create('tbl_story_highlight_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('highlight_id');
            $table->unsignedBigInteger('original_story_id')->nullable();
            $table->smallInteger('type')->default(0); // 0=image, 1=video
            $table->string('content', 999);
            $table->string('thumbnail', 999)->nullable();
            $table->string('duration', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->foreign('highlight_id')->references('id')->on('tbl_story_highlights')->onDelete('cascade');
            $table->index(['highlight_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_story_highlight_items');
        Schema::dropIfExists('tbl_story_highlights');
    }
};
