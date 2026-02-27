<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_livestream_replays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('room_id', 100);
            $table->string('title', 255)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('recording_url', 500)->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->integer('peak_viewers')->default(0);
            $table->integer('total_likes')->default(0);
            $table->integer('total_gifts_coins')->default(0);
            $table->integer('view_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_livestream_replays');
    }
};
