<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // --- tbl_users: Instagram connection fields ---
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('instagram_user_id', 100)->nullable()->after('profile_sub_category_id');
            $table->text('instagram_access_token')->nullable()->after('instagram_user_id');
            $table->timestamp('instagram_token_expires_at')->nullable()->after('instagram_access_token');
            $table->boolean('instagram_auto_sync')->default(false)->after('instagram_token_expires_at');
            $table->timestamp('instagram_last_sync_at')->nullable()->after('instagram_auto_sync');
        });

        // Partial index for fast lookup of connected users
        DB::statement('CREATE INDEX idx_users_instagram_connected ON tbl_users (instagram_user_id) WHERE instagram_user_id IS NOT NULL');

        // --- tbl_settings: Instagram admin config ---
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('instagram_import_enabled')->default(false)->after('ima_postroll_ad_tag_ios');
            $table->string('instagram_app_id', 100)->nullable()->after('instagram_import_enabled');
            $table->text('instagram_app_secret')->nullable()->after('instagram_app_id');
            $table->string('instagram_redirect_uri', 500)->nullable()->after('instagram_app_secret');
        });

        // --- tbl_instagram_imports: tracking table ---
        Schema::create('tbl_instagram_imports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('instagram_media_id', 100);
            $table->unsignedBigInteger('post_id')->nullable();
            $table->string('media_type', 20);
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('set null');
            $table->unique(['user_id', 'instagram_media_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_instagram_imports');

        DB::statement('DROP INDEX IF EXISTS idx_users_instagram_connected');

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_user_id',
                'instagram_access_token',
                'instagram_token_expires_at',
                'instagram_auto_sync',
                'instagram_last_sync_at',
            ]);
        });

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_import_enabled',
                'instagram_app_id',
                'instagram_app_secret',
                'instagram_redirect_uri',
            ]);
        });
    }
};
