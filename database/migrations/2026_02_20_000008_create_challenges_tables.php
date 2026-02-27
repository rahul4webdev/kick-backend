<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_challenges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('creator_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('hashtag', 100);
            $table->text('rules')->nullable();
            $table->smallInteger('challenge_type')->default(0); // 0=community, 1=brand
            $table->string('cover_image', 500)->nullable();
            $table->string('preview_video', 500)->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->smallInteger('prize_type')->default(0); // 0=none, 1=coins, 2=badge
            $table->integer('prize_amount')->default(0);
            $table->integer('entry_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('status')->default(1); // 1=active, 2=ended, 3=judging, 4=completed
            $table->timestampsTz();

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['status', 'is_active']);
            $table->index('hashtag');
        });

        Schema::create('tbl_challenge_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('challenge_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('score')->default(0);
            $table->integer('rank')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('challenge_id')->references('id')->on('tbl_challenges')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['challenge_id', 'post_id']);
            $table->index(['challenge_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_challenge_entries');
        Schema::dropIfExists('tbl_challenges');
    }
};
