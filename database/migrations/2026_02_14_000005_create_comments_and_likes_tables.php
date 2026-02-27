<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->string('comment', 999)->nullable();
            $table->string('mentioned_user_ids', 999)->nullable();
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('replies_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->smallInteger('type')->default(0);
            $table->timestampsTz();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('post_id');
            $table->index('user_id');
        });

        DB::statement('ALTER TABLE tbl_comments ADD CONSTRAINT chk_comment_type CHECK (type IN (0, 1))');

        Schema::create('comment_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');
            $table->text('reply')->nullable();
            $table->string('mentioned_user_ids')->nullable();
            $table->timestampsTz();

            $table->foreign('comment_id')->references('id')->on('tbl_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('comment_id');
            $table->index('user_id');
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampsTz();

            $table->foreign('comment_id')->references('id')->on('tbl_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['comment_id', 'user_id']);
        });

        Schema::create('tbl_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampsTz();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('post_saves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampsTz();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_saves');
        Schema::dropIfExists('tbl_likes');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('comment_replies');
        Schema::dropIfExists('tbl_comments');
    }
};
