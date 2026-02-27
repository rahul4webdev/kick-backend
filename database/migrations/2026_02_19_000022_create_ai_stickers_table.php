<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_ai_stickers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('prompt', 500);
            $table->string('image_url', 500);
            $table->boolean('is_public')->default(false);
            $table->integer('use_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['is_public', 'use_count']);
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
        });

        // Add AI image generation settings
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('ai_sticker_enabled')->default(true);
            $table->string('ai_image_api_key', 500)->nullable();
            $table->string('ai_image_provider', 50)->default('openai'); // openai, stability
            $table->string('ai_image_model', 100)->default('dall-e-3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ai_stickers');
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_sticker_enabled',
                'ai_image_api_key',
                'ai_image_provider',
                'ai_image_model',
            ]);
        });
    }
};
