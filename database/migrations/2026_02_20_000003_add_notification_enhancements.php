<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix the CHECK constraint that was originally 1-8 but types 9-20+ now exist
        DB::statement('ALTER TABLE notification_users DROP CONSTRAINT IF EXISTS chk_notification_type');

        // 2. Add new columns for enhanced notification center
        Schema::table('notification_users', function (Blueprint $table) {
            $table->string('category', 50)->default('general');
            $table->boolean('is_read')->default(false);
            $table->index(['to_user_id', 'category'], 'idx_notif_category');
            $table->index(['to_user_id', 'is_read'], 'idx_notif_read');
        });

        // 3. Backfill category for existing notifications based on type
        DB::statement("
            UPDATE notification_users SET category = CASE
                WHEN type = 1 THEN 'likes'
                WHEN type IN (2, 7, 8) THEN 'comments'
                WHEN type IN (3, 4) THEN 'mentions'
                WHEN type IN (5, 9) THEN 'follows'
                WHEN type = 6 THEN 'gifts'
                ELSE 'system'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('notification_users', function (Blueprint $table) {
            $table->dropIndex('idx_notif_category');
            $table->dropIndex('idx_notif_read');
            $table->dropColumn(['category', 'is_read']);
        });

        DB::statement('ALTER TABLE notification_users ADD CONSTRAINT chk_notification_type CHECK (type BETWEEN 1 AND 8)');
    }
};
