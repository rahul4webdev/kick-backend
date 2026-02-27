<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_story_chains', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('prompt', 300);
            $table->unsignedBigInteger('creator_id');
            $table->unsignedBigInteger('origin_story_id')->nullable();
            $table->unsignedInteger('participant_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('origin_story_id')->references('id')->on('stories')->onDelete('set null');
            $table->index(['is_active', 'created_at']);
        });

        // Track which stories belong to a chain
        Schema::create('tbl_story_chain_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chain_id');
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampsTz();

            $table->foreign('chain_id')->references('id')->on('tbl_story_chains')->onDelete('cascade');
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['chain_id', 'user_id']);
            $table->index(['chain_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_story_chain_participants');
        Schema::dropIfExists('tbl_story_chains');
    }
};
