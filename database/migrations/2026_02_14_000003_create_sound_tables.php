<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_sound_category', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50);
            $table->string('image', 999)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestampsTz();
        });

        Schema::create('tbl_sound', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->bigInteger('post_count')->default(0);
            $table->smallInteger('added_by')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 250);
            $table->string('sound', 200);
            $table->string('duration', 100)->nullable();
            $table->string('artist', 100)->nullable();
            $table->string('image', 999)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestampsTz();

            $table->foreign('category_id')->references('id')->on('tbl_sound_category')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('set null');
            $table->index('category_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_sound');
        Schema::dropIfExists('tbl_sound_category');
    }
};
