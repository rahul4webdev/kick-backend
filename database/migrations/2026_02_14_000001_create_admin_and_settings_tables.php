<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_admin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('admin_name', 50)->nullable();
            $table->string('admin_username', 100)->unique();
            $table->text('admin_password');
            $table->string('admin_profile', 100)->nullable();
            $table->smallInteger('user_type')->default(0);
            $table->timestampsTz();
        });

        Schema::create('tbl_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('app_name')->default('Shortzz');
            $table->string('currency')->default('$');
            $table->double('coin_value')->default(0.01);
            $table->integer('min_redeem_coins')->default(100);

            // Limits
            $table->integer('max_upload_daily')->default(10);
            $table->integer('max_comment_daily')->default(50);
            $table->integer('max_comment_reply_daily')->default(50);
            $table->integer('max_story_daily')->default(10);
            $table->integer('max_comment_pins')->default(3);
            $table->integer('max_post_pins')->default(3);
            $table->integer('max_user_links')->default(5);
            $table->integer('max_images_per_post')->default(10);

            // Google/Firebase
            $table->text('place_api_access_token')->nullable();

            // Legal
            $table->text('privacy_policy')->nullable();
            $table->text('terms_of_uses')->nullable();

            // AdMob
            $table->boolean('admob_android_status')->default(false);
            $table->boolean('admob_ios_status')->default(false);
            $table->string('admob_banner')->nullable();
            $table->string('admob_int')->nullable();
            $table->string('admob_banner_ios')->nullable();
            $table->string('admob_int_ios')->nullable();

            // Live Stream
            $table->boolean('live_dummy_show')->default(false);
            $table->boolean('live_battle')->default(false);
            $table->integer('min_followers_for_live')->default(0);
            $table->integer('live_min_viewers')->default(0);
            $table->integer('live_timeout')->default(0);
            $table->string('zego_app_sign')->nullable();
            $table->string('zego_app_id')->nullable();

            // DeepAR
            $table->boolean('is_deepAR')->default(false);
            $table->string('deepar_android_key')->nullable();
            $table->string('deepar_iOS_key')->nullable();

            // GIF
            $table->boolean('gif_support')->default(false);
            $table->string('giphy_key')->nullable();

            // Content Moderation
            $table->boolean('is_content_moderation')->default(false);
            $table->string('sight_engine_api_user')->nullable();
            $table->string('sight_engine_api_secret')->nullable();
            $table->string('sight_engine_video_workflow_id')->nullable();
            $table->string('sight_engine_image_workflow_id')->nullable();

            // Misc
            $table->boolean('is_compress')->default(false);
            $table->boolean('is_withdrawal_on')->default(false);
            $table->boolean('registration_bonus_status')->default(false);
            $table->integer('registration_bonus_amount')->default(0);
            $table->string('help_mail')->nullable();

            // Watermark
            $table->boolean('watermark_status')->default(false);
            $table->string('watermark_image')->nullable();

            // Download links
            $table->string('app_store_download_link')->nullable();
            $table->string('play_store_download_link')->nullable();

            // URI Scheme
            $table->string('uri_scheme')->nullable();

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_settings');
        Schema::dropIfExists('tbl_admin');
    }
};
