<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 50); // followers_1k, followers_10k, viral_post, anniversary_1y, etc.
            $table->unsignedBigInteger('data_id')->nullable(); // post_id for viral, null for followers/anniversary
            $table->jsonb('metadata')->nullable(); // extra data (follower_count at time, post stats, etc.)
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_milestones');
    }
};
