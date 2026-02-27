<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profile_user_id'); // The user whose profile this Q is on
            $table->unsignedBigInteger('asked_by_user_id'); // Who asked
            $table->text('question');
            $table->text('answer')->nullable();
            $table->timestampTz('answered_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_hidden')->default(false); // Profile owner can hide
            $table->integer('like_count')->default(0);
            $table->timestampsTz();

            $table->foreign('profile_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('asked_by_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['profile_user_id', 'is_hidden', 'created_at']);
        });

        Schema::create('tbl_question_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampsTz();

            $table->foreign('question_id')->references('id')->on('tbl_questions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['question_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_question_likes');
        Schema::dropIfExists('tbl_questions');
    }
};
