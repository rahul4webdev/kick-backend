<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add sticker_data column to stories table for storing interactive sticker metadata
        Schema::table('stories', function (Blueprint $table) {
            $table->jsonb('sticker_data')->nullable()->after('duration');
        });

        // Create story interactions table for votes, question responses, etc.
        Schema::create('tbl_story_interactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('user_id');
            $table->string('interaction_type', 30); // poll_vote, question_response
            $table->jsonb('data')->nullable(); // {option_index: 0} or {response: "text"}
            $table->timestampsTz();

            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');

            // One vote/response per user per story per type
            $table->unique(['story_id', 'user_id', 'interaction_type'], 'unique_story_user_interaction');
            $table->index(['story_id', 'interaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_story_interactions');

        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('sticker_data');
        });
    }
};
