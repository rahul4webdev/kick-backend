<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_user_mute', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->boolean('mute_posts')->default(true);
            $table->boolean('mute_stories')->default(true);
            $table->timestampsTz();

            $table->foreign('from_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['from_user_id', 'to_user_id']);
            $table->index('to_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_mute');
    }
};
