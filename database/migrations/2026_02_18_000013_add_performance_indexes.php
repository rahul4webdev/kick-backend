<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // User blocks — called 15+ times per request cycle via getUsersBlockedUsersIdsArray
        if (Schema::hasTable('tbl_user_block')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_user_blocks_from ON tbl_user_block (from_user_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_user_blocks_to ON tbl_user_block (to_user_id)');
        }

        // Post user timeline — fetchUserPosts ordering
        if (Schema::hasTable('tbl_post')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_post_user_created ON tbl_post (user_id, created_at DESC)');
            // Post created_at for general DESC ordering (discover, following feeds)
            DB::statement('CREATE INDEX IF NOT EXISTS idx_post_created_desc ON tbl_post (created_at DESC)');
        }

        // Notifications — fetchActivityNotifications ordering
        if (Schema::hasTable('notification_users')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_to_created ON notification_users (to_user_id, created_at DESC)');
        }

        // Hashtags — trending sort by post_count
        if (Schema::hasTable('tbl_hash_tags')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_hashtags_post_count ON tbl_hash_tags (post_count DESC)');
        }

        // Post saves — for fetchSavedPosts lookup
        if (Schema::hasTable('post_saves')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_post_saves_user ON post_saves (user_id, created_at DESC)');
        }

        // Followers — from_user_id for following feed
        if (Schema::hasTable('tbl_followers')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_followers_from ON tbl_followers (from_user_id)');
        }
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS idx_user_blocks_from');
        DB::statement('DROP INDEX IF EXISTS idx_user_blocks_to');
        DB::statement('DROP INDEX IF EXISTS idx_post_user_created');
        DB::statement('DROP INDEX IF EXISTS idx_post_created_desc');
        DB::statement('DROP INDEX IF EXISTS idx_notifications_to_created');
        DB::statement('DROP INDEX IF EXISTS idx_hashtags_post_count');
        DB::statement('DROP INDEX IF EXISTS idx_post_saves_user');
        DB::statement('DROP INDEX IF EXISTS idx_followers_from');
    }
};
