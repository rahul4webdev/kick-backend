<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            // ─── VAST Ad Controls ───
            $table->boolean('ima_preroll_enabled')->default(true)->after('ima_postroll_ad_tag_ios');
            $table->boolean('ima_midroll_enabled')->default(true)->after('ima_preroll_enabled');
            $table->boolean('ima_postroll_enabled')->default(true)->after('ima_midroll_enabled');

            // ─── Minimum video length (seconds) for each ad type ───
            $table->integer('ima_preroll_min_video_length')->default(0)->after('ima_postroll_enabled');
            $table->integer('ima_midroll_min_video_length')->default(30)->after('ima_preroll_min_video_length');
            $table->integer('ima_postroll_min_video_length')->default(15)->after('ima_midroll_min_video_length');

            // ─── Preload timing (seconds before video end) ───
            $table->integer('ima_preload_seconds_before')->default(10)->after('ima_postroll_min_video_length');

            // ─── VAST Feed Video Ads ───
            $table->boolean('vast_feed_ad_enabled')->default(false)->after('ima_preload_seconds_before');
            $table->string('vast_feed_ad_tag_android', 1024)->nullable()->after('vast_feed_ad_enabled');
            $table->string('vast_feed_ad_tag_ios', 1024)->nullable()->after('vast_feed_ad_tag_android');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ima_preroll_enabled', 'ima_midroll_enabled', 'ima_postroll_enabled',
                'ima_preroll_min_video_length', 'ima_midroll_min_video_length', 'ima_postroll_min_video_length',
                'ima_preload_seconds_before',
                'vast_feed_ad_enabled', 'vast_feed_ad_tag_android', 'vast_feed_ad_tag_ios',
            ]);
        });
    }
};
