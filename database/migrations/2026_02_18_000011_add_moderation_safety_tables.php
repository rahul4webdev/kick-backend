<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. User violations tracking
        Schema::create('tbl_user_violations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('moderator_id')->nullable();
            $table->smallInteger('severity')->default(1); // 1=warning, 2=minor, 3=major, 4=critical
            $table->string('reason');
            $table->text('description')->nullable();
            $table->bigInteger('reference_post_id')->nullable();
            $table->bigInteger('reference_report_id')->nullable();
            $table->string('action_taken')->default('warning'); // warning, temp_ban, permanent_ban, post_removed
            $table->integer('ban_days')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'created_at']);
        });

        // 2. Banned words / phrases
        Schema::create('tbl_banned_words', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->smallInteger('severity')->default(1); // 1=filter, 2=warn, 3=block
            $table->boolean('is_active')->default(true);
            $table->string('category')->default('general'); // general, hate_speech, sexual, spam
            $table->timestamps();

            $table->index(['is_active', 'severity']);
        });

        // 3. Moderation audit log
        Schema::create('tbl_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('moderator_id');
            $table->string('action'); // freeze_user, unfreeze_user, delete_post, delete_story, accept_report, reject_report, warn_user, ban_user
            $table->string('target_type'); // user, post, story, report
            $table->bigInteger('target_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('moderator_id');
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });

        // 4. Add ban_until and ban_reason to users for temporary bans
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->timestamp('ban_until')->nullable();
            $table->string('ban_reason')->nullable();
            $table->integer('violation_count')->default(0);
        });

        // 5. Add status to report tables
        Schema::table('report_posts', function (Blueprint $table) {
            $table->smallInteger('status')->default(0); // 0=pending, 1=reviewing, 2=resolved, 3=dismissed
            $table->bigInteger('reviewed_by')->nullable();
            $table->index('status');
        });

        Schema::table('report_user', function (Blueprint $table) {
            $table->smallInteger('status')->default(0);
            $table->bigInteger('reviewed_by')->nullable();
            $table->index('status');
        });

        // Seed common banned words
        DB::table('tbl_banned_words')->insert([
            ['word' => 'spam', 'severity' => 1, 'category' => 'spam', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['word' => 'scam', 'severity' => 2, 'category' => 'spam', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_violations');
        Schema::dropIfExists('tbl_banned_words');
        Schema::dropIfExists('tbl_moderation_logs');

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['ban_until', 'ban_reason', 'violation_count']);
        });

        Schema::table('report_posts', function (Blueprint $table) {
            $table->dropColumn(['status', 'reviewed_by']);
        });

        Schema::table('report_user', function (Blueprint $table) {
            $table->dropColumn(['status', 'reviewed_by']);
        });
    }
};
