<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            // Content type: 0=normal, 1=music_video, 2=trailer, 3=news, 4=short_story
            $table->smallInteger('content_type')->default(0)->after('post_type');
            // JSONB for type-specific metadata (genre, language, artist, release_date, etc.)
            $table->jsonb('content_metadata')->nullable()->after('content_type');
            // Video linking: link to previous part for multi-part content
            $table->unsignedBigInteger('linked_previous_post_id')->nullable()->after('content_metadata');
            // Featured content flag (admin can mark content as featured)
            $table->boolean('is_featured')->default(false)->after('is_trending');

            $table->foreign('linked_previous_post_id')->references('id')->on('tbl_post')->onDelete('set null');
        });

        // Indexes for content type queries
        DB::statement('CREATE INDEX idx_post_content_type ON tbl_post (content_type)');
        DB::statement('CREATE INDEX idx_post_content_type_created ON tbl_post (content_type, created_at DESC)');
        DB::statement('CREATE INDEX idx_post_featured ON tbl_post (is_featured) WHERE is_featured = true');

        // Add part transition ad settings to tbl_settings
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('part_transition_ad_enabled')->default(false);
            $table->integer('part_transition_ad_start_at')->default(3); // Start showing ads at part 3
            $table->integer('part_transition_ad_interval')->default(2); // Then every 2 parts
            // IMA ad tags for content types
            $table->string('ima_content_preroll_ad_tag_android', 1024)->nullable();
            $table->string('ima_content_preroll_ad_tag_ios', 1024)->nullable();
            $table->string('ima_content_midroll_ad_tag_android', 1024)->nullable();
            $table->string('ima_content_midroll_ad_tag_ios', 1024)->nullable();
            $table->string('ima_content_postroll_ad_tag_android', 1024)->nullable();
            $table->string('ima_content_postroll_ad_tag_ios', 1024)->nullable();
            $table->boolean('ima_content_preroll_enabled')->default(false);
            $table->boolean('ima_content_midroll_enabled')->default(false);
            $table->boolean('ima_content_postroll_enabled')->default(false);
            $table->integer('ima_content_midroll_min_duration')->default(120); // Min video duration for mid-roll (seconds)
            $table->integer('ima_content_preroll_skip_timer')->default(5); // Skip after N seconds
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropForeign(['linked_previous_post_id']);
            $table->dropColumn(['content_type', 'content_metadata', 'linked_previous_post_id', 'is_featured']);
        });

        DB::statement('DROP INDEX IF EXISTS idx_post_content_type');
        DB::statement('DROP INDEX IF EXISTS idx_post_content_type_created');
        DB::statement('DROP INDEX IF EXISTS idx_post_featured');

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'part_transition_ad_enabled', 'part_transition_ad_start_at', 'part_transition_ad_interval',
                'ima_content_preroll_ad_tag_android', 'ima_content_preroll_ad_tag_ios',
                'ima_content_midroll_ad_tag_android', 'ima_content_midroll_ad_tag_ios',
                'ima_content_postroll_ad_tag_android', 'ima_content_postroll_ad_tag_ios',
                'ima_content_preroll_enabled', 'ima_content_midroll_enabled', 'ima_content_postroll_enabled',
                'ima_content_midroll_min_duration', 'ima_content_preroll_skip_timer',
            ]);
        });
    }
};
