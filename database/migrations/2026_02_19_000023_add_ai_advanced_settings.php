<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('ai_translation_enabled')->default(true);
            $table->boolean('ai_content_ideas_enabled')->default(true);
            $table->boolean('ai_voice_enhancement_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['ai_translation_enabled', 'ai_content_ideas_enabled', 'ai_voice_enhancement_enabled']);
        });
    }
};
