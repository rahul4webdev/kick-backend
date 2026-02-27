<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            // Drop Zego columns
            $table->dropColumn(['zego_app_id', 'zego_app_sign']);

            // Add LiveKit columns
            $table->string('livekit_host', 500)->nullable();
            $table->string('livekit_api_key', 255)->nullable();
            $table->string('livekit_api_secret', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['livekit_host', 'livekit_api_key', 'livekit_api_secret']);
            $table->string('zego_app_id', 999)->nullable();
            $table->string('zego_app_sign', 999)->nullable();
        });
    }
};
