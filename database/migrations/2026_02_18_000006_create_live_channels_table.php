<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_live_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('channel_name', 200);
            $table->string('channel_logo', 500)->nullable();
            $table->string('stream_url', 500);
            $table->string('stream_type', 20)->default('hls'); // hls, youtube, rtmp
            $table->string('category', 100)->nullable();
            $table->string('language', 50)->nullable();
            $table->boolean('is_live')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('viewer_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['is_active', 'is_live'], 'idx_live_channels_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_live_channels');
    }
};
