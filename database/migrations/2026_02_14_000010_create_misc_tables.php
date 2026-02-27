<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title', 555)->nullable();
            $table->string('url', 555)->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('user_id');
        });

        Schema::create('user_levels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('level')->default(0);
            $table->integer('coins_collection')->default(0);
            $table->timestampsTz();
        });

        Schema::create('user_supports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->smallInteger('status')->default(0);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('user_id');
        });

        Schema::create('daily_active_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->integer('user_count')->default(0);
            $table->timestampsTz();

            $table->unique('date');
        });

        Schema::create('deepar_filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 25)->nullable();
            $table->string('image', 999)->nullable();
            $table->string('filter_file', 999)->nullable();
            $table->timestampsTz();
        });

        Schema::create('dummy_live_videos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title')->nullable();
            $table->string('link')->nullable();
            $table->boolean('status')->default(true);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('set null');
        });

        Schema::create('restriction_username', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username')->unique();
            $table->timestampsTz();
        });

        Schema::create('onboarding_screens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('position')->default(0);
            $table->string('image', 999)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('description', 999)->nullable();
            $table->timestampsTz();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('title');
            $table->string('localized_title')->nullable();
            $table->string('csv_file')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
        Schema::dropIfExists('onboarding_screens');
        Schema::dropIfExists('restriction_username');
        Schema::dropIfExists('dummy_live_videos');
        Schema::dropIfExists('deepar_filters');
        Schema::dropIfExists('daily_active_users');
        Schema::dropIfExists('user_supports');
        Schema::dropIfExists('user_levels');
        Schema::dropIfExists('user_links');
    }
};
