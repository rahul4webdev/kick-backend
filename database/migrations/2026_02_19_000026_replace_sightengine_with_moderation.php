<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sight_engine_api_user',
                'sight_engine_api_secret',
                'sight_engine_image_workflow_id',
                'sight_engine_video_workflow_id',
            ]);

            $table->string('moderation_cloudflare_url', 500)->nullable()->after('is_content_moderation');
            $table->string('moderation_cloudflare_token', 255)->nullable()->after('moderation_cloudflare_url');
            $table->string('moderation_self_hosted_url', 500)->nullable()->after('moderation_cloudflare_token');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'moderation_cloudflare_url',
                'moderation_cloudflare_token',
                'moderation_self_hosted_url',
            ]);

            $table->string('sight_engine_api_user')->nullable();
            $table->string('sight_engine_api_secret')->nullable();
            $table->string('sight_engine_video_workflow_id')->nullable();
            $table->string('sight_engine_image_workflow_id')->nullable();
        });
    }
};
