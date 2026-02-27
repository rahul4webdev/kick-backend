<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Reposts table ──────────────────────────────────────────
        Schema::create('tbl_reposts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('original_post_id');
            $table->text('caption')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index('original_post_id');
            $table->unique(['user_id', 'original_post_id']);
        });

        // Add repost_count to posts
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->integer('repost_count')->default(0);
        });

        // ─── Comment reactions table ────────────────────────────────
        Schema::create('tbl_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('comment_id');
            $table->string('emoji', 10);
            $table->timestamps();
            $table->unique(['user_id', 'comment_id', 'emoji']);
            $table->index(['comment_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_comment_reactions');
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn('repost_count');
        });
        Schema::dropIfExists('tbl_reposts');
    }
};
