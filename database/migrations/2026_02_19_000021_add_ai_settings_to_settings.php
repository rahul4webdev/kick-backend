<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('ai_chatbot_enabled')->default(true);
            $table->string('ai_api_key', 500)->nullable();
            $table->string('ai_model', 100)->default('claude-sonnet-4-5-20250929');
            $table->text('ai_system_prompt')->nullable();
            $table->string('ai_bot_name', 100)->default('AI Assistant');
            $table->string('ai_bot_avatar', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_chatbot_enabled',
                'ai_api_key',
                'ai_model',
                'ai_system_prompt',
                'ai_bot_name',
                'ai_bot_avatar',
            ]);
        });
    }
};
