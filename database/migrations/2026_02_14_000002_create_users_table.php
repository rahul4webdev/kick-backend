<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('is_dummy')->default(false);
            $table->text('identity')->nullable();
            $table->string('fullname', 50)->nullable();
            $table->string('username', 50)->unique();
            $table->string('user_email', 50)->nullable();
            $table->integer('mobile_country_code')->nullable();
            $table->string('user_mobile_no', 20)->nullable();
            $table->string('profile_photo', 999)->nullable();
            $table->string('login_method', 10)->nullable();
            $table->smallInteger('device')->default(0);
            $table->text('device_token')->nullable();

            // Notification preferences
            $table->boolean('notify_post_like')->default(true);
            $table->boolean('notify_post_comment')->default(true);
            $table->boolean('notify_follow')->default(true);
            $table->boolean('notify_mention')->default(true);
            $table->boolean('notify_gift_received')->default(true);
            $table->boolean('notify_chat')->default(true);

            // Verification & status
            $table->boolean('is_verify')->default(false);
            $table->smallInteger('who_can_view_post')->default(0);
            $table->boolean('show_my_following')->default(true);
            $table->smallInteger('receive_message')->default(0);

            // Wallet
            $table->bigInteger('coin_wallet')->default(0);
            $table->bigInteger('coin_collected_lifetime')->default(0);
            $table->bigInteger('coin_gifted_lifetime')->default(0);
            $table->bigInteger('coin_purchased_lifetime')->default(0);

            // Bio
            $table->string('bio', 555)->nullable();

            // Stats (denormalized counters)
            $table->bigInteger('follower_count')->default(0);
            $table->bigInteger('following_count')->default(0);
            $table->bigInteger('total_post_likes_count')->default(0);

            // Status
            $table->boolean('is_freez')->default(false);

            // Geo
            $table->string('country', 55)->nullable();
            $table->string('countryCode', 10)->nullable();
            $table->string('region', 10)->nullable();
            $table->string('regionName', 55)->nullable();
            $table->string('city', 55)->nullable();
            $table->float('lat')->nullable();
            $table->float('lon')->nullable();
            $table->string('timezone', 50)->nullable();

            // App usage
            $table->timestamp('app_last_used_at')->nullable();
            $table->text('saved_music_ids')->nullable();
            $table->boolean('is_moderator')->default(false);
            $table->string('app_language', 10)->default('en');
            $table->string('password', 555)->nullable();

            $table->timestampsTz();
        });

        // Partial index: only active (non-frozen) users for feed queries
        DB::statement('CREATE INDEX idx_users_active ON tbl_users (id) WHERE is_freez = false');

        Schema::create('user_auth_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->text('auth_token');
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Hash index on auth_token for fast lookups
        DB::statement('CREATE INDEX idx_auth_token ON user_auth_tokens USING hash (auth_token)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_auth_tokens');
        Schema::dropIfExists('tbl_users');
    }
};
