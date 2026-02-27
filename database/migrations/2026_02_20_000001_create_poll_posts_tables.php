<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_polls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->string('question', 500);
            $table->smallInteger('poll_type')->default(0); // 0=text, 1=image
            $table->boolean('allow_multiple')->default(false);
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->integer('total_votes')->default(0);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
        });

        Schema::create('tbl_poll_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('poll_id');
            $table->string('option_text', 200);
            $table->string('option_image', 500)->nullable();
            $table->integer('vote_count')->default(0);
            $table->integer('sort_order')->default(0);

            $table->foreign('poll_id')->references('id')->on('tbl_polls')->onDelete('cascade');
        });

        Schema::create('tbl_poll_votes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('poll_id');
            $table->unsignedBigInteger('option_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('poll_id')->references('id')->on('tbl_polls')->onDelete('cascade');
            $table->foreign('option_id')->references('id')->on('tbl_poll_options')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['poll_id', 'user_id']);
            $table->index('poll_id', 'idx_poll_votes_poll');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_poll_votes');
        Schema::dropIfExists('tbl_poll_options');
        Schema::dropIfExists('tbl_polls');
    }
};
