<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_playlists', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->integer('post_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'sort_order']);
        });

        Schema::create('tbl_playlist_posts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('playlist_id');
            $table->bigInteger('post_id');
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->foreign('playlist_id')->references('id')->on('tbl_playlists')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->unique(['playlist_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_playlist_posts');
        Schema::dropIfExists('tbl_playlists');
    }
};
