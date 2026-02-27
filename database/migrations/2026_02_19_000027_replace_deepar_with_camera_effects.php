<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old DeepAR filters table
        Schema::dropIfExists('deepar_filters');

        // Create color_filters table
        Schema::create('color_filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 100);
            $table->string('image', 999)->nullable();
            $table->json('color_matrix')->nullable();
            $table->double('brightness')->default(0);
            $table->double('contrast')->default(1.0);
            $table->double('saturation')->default(1.0);
            $table->double('warmth')->default(0);
            $table->integer('blur_intensity')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('status')->default(true);
            $table->timestampsTz();
        });

        // Create face_stickers table
        Schema::create('face_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 100);
            $table->string('thumbnail', 999)->nullable();
            $table->string('sticker_image', 999)->nullable();
            $table->string('anchor_landmark', 50)->default('nose');
            $table->double('scale')->default(1.0);
            $table->double('offset_x')->default(0);
            $table->double('offset_y')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('status')->default(true);
            $table->timestampsTz();
        });

        // Update settings table
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['is_deepAR', 'deepar_android_key', 'deepar_iOS_key']);
        });

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('is_camera_effects')->default(true)->after('is_content_moderation');
            $table->string('snap_camera_kit_app_id', 255)->nullable()->after('is_camera_effects');
            $table->string('snap_camera_kit_api_token', 255)->nullable()->after('snap_camera_kit_app_id');
            $table->string('snap_camera_kit_group_id', 255)->nullable()->after('snap_camera_kit_api_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('color_filters');
        Schema::dropIfExists('face_stickers');

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['is_camera_effects', 'snap_camera_kit_app_id', 'snap_camera_kit_api_token', 'snap_camera_kit_group_id']);
        });

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('is_deepAR')->default(false);
            $table->string('deepar_android_key', 255)->nullable();
            $table->string('deepar_iOS_key', 255)->nullable();
        });

        // Recreate original table
        Schema::create('deepar_filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 25)->nullable();
            $table->string('image', 999)->nullable();
            $table->string('filter_file', 999)->nullable();
            $table->timestampsTz();
        });
    }
};
