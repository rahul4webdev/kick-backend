<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('type');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->unsignedBigInteger('data_id')->nullable();
            $table->timestampsTz();

            $table->foreign('from_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['to_user_id', 'type']);
            $table->index('from_user_id');
        });

        DB::statement('ALTER TABLE notification_users ADD CONSTRAINT chk_notification_type CHECK (type BETWEEN 1 AND 8)');

        Schema::create('notification_admin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestampsTz();
        });

        Schema::create('report_reasons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 255);
            $table->timestampsTz();
        });

        Schema::create('report_posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('by_user_id');
            $table->unsignedBigInteger('post_id');
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->foreign('by_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->index('post_id');
        });

        Schema::create('report_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('by_user_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->foreign('by_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_user');
        Schema::dropIfExists('report_posts');
        Schema::dropIfExists('report_reasons');
        Schema::dropIfExists('notification_admin');
        Schema::dropIfExists('notification_users');
    }
};
