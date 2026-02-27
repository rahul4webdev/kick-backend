<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_broadcast_channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('creator_user_id');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->integer('member_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('creator_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['creator_user_id', 'is_active']);
        });

        Schema::create('tbl_broadcast_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->useCurrent();

            $table->foreign('channel_id')->references('id')->on('tbl_broadcast_channels')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['channel_id', 'user_id']);
            $table->index('user_id');
            $table->index('channel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_broadcast_members');
        Schema::dropIfExists('tbl_broadcast_channels');
    }
};
