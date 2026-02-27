<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            // ─── Meta Audience Network ───
            $table->boolean('meta_ads_enabled')->default(false)->after('admob_ios_status');
            $table->string('meta_banner_android', 255)->nullable()->after('meta_ads_enabled');
            $table->string('meta_banner_ios', 255)->nullable()->after('meta_banner_android');
            $table->string('meta_interstitial_android', 255)->nullable()->after('meta_banner_ios');
            $table->string('meta_interstitial_ios', 255)->nullable()->after('meta_interstitial_android');
            $table->string('meta_rewarded_android', 255)->nullable()->after('meta_interstitial_ios');
            $table->string('meta_rewarded_ios', 255)->nullable()->after('meta_rewarded_android');

            // ─── Unity Ads ───
            $table->boolean('unity_ads_enabled')->default(false)->after('meta_rewarded_ios');
            $table->string('unity_game_id_android', 255)->nullable()->after('unity_ads_enabled');
            $table->string('unity_game_id_ios', 255)->nullable()->after('unity_game_id_android');
            $table->string('unity_banner_android', 255)->nullable()->after('unity_game_id_ios');
            $table->string('unity_banner_ios', 255)->nullable()->after('unity_banner_android');
            $table->string('unity_interstitial_android', 255)->nullable()->after('unity_banner_ios');
            $table->string('unity_interstitial_ios', 255)->nullable()->after('unity_interstitial_android');
            $table->string('unity_rewarded_android', 255)->nullable()->after('unity_interstitial_ios');
            $table->string('unity_rewarded_ios', 255)->nullable()->after('unity_rewarded_android');

            // ─── AppLovin ───
            $table->boolean('applovin_enabled')->default(false)->after('unity_rewarded_ios');
            $table->string('applovin_sdk_key', 255)->nullable()->after('applovin_enabled');
            $table->string('applovin_banner_android', 255)->nullable()->after('applovin_sdk_key');
            $table->string('applovin_banner_ios', 255)->nullable()->after('applovin_banner_android');
            $table->string('applovin_interstitial_android', 255)->nullable()->after('applovin_banner_ios');
            $table->string('applovin_interstitial_ios', 255)->nullable()->after('applovin_interstitial_android');
            $table->string('applovin_rewarded_android', 255)->nullable()->after('applovin_interstitial_ios');
            $table->string('applovin_rewarded_ios', 255)->nullable()->after('applovin_rewarded_android');

            // ─── Waterfall Priority (JSON arrays) ───
            $table->json('waterfall_banner_priority')->nullable()->after('applovin_rewarded_ios');
            $table->json('waterfall_interstitial_priority')->nullable()->after('waterfall_banner_priority');
            $table->json('waterfall_rewarded_priority')->nullable()->after('waterfall_interstitial_priority');

            // ─── IMA Pre-Roll ───
            $table->integer('ima_preroll_frequency')->default(0)->after('waterfall_rewarded_priority');
            $table->string('ima_ad_tag_android', 1024)->nullable()->after('ima_preroll_frequency');
            $table->string('ima_ad_tag_ios', 1024)->nullable()->after('ima_ad_tag_android');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'meta_ads_enabled',
                'meta_banner_android', 'meta_banner_ios',
                'meta_interstitial_android', 'meta_interstitial_ios',
                'meta_rewarded_android', 'meta_rewarded_ios',
                'unity_ads_enabled',
                'unity_game_id_android', 'unity_game_id_ios',
                'unity_banner_android', 'unity_banner_ios',
                'unity_interstitial_android', 'unity_interstitial_ios',
                'unity_rewarded_android', 'unity_rewarded_ios',
                'applovin_enabled', 'applovin_sdk_key',
                'applovin_banner_android', 'applovin_banner_ios',
                'applovin_interstitial_android', 'applovin_interstitial_ios',
                'applovin_rewarded_android', 'applovin_rewarded_ios',
                'waterfall_banner_priority',
                'waterfall_interstitial_priority',
                'waterfall_rewarded_priority',
                'ima_preroll_frequency',
                'ima_ad_tag_android', 'ima_ad_tag_ios',
            ]);
        });
    }
};
