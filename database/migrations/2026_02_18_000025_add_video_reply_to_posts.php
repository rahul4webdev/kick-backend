<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add reply_to_comment_id to posts for video replies to comments
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->bigInteger('reply_to_comment_id')->nullable();
            $table->string('reply_to_comment_text', 999)->nullable();
            $table->index('reply_to_comment_id');
        });

        // Add video_reply_count to comments
        Schema::table('tbl_comments', function (Blueprint $table) {
            $table->bigInteger('video_reply_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropIndex(['reply_to_comment_id']);
            $table->dropColumn(['reply_to_comment_id', 'reply_to_comment_text']);
        });
        Schema::table('tbl_comments', function (Blueprint $table) {
            $table->dropColumn('video_reply_count');
        });
    }
};
