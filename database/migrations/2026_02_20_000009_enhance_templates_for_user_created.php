<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable()->after('id');
            $table->boolean('is_user_created')->default(false)->after('is_active');
            $table->unsignedBigInteger('source_post_id')->nullable()->after('is_user_created');
            $table->integer('trending_score')->default(0)->after('use_count');
            $table->integer('like_count')->default(0)->after('trending_score');

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('set null');
            $table->index(['is_user_created', 'trending_score']);
        });

        Schema::create('tbl_template_uses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('template_id')->references('id')->on('tbl_templates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['template_id', 'created_at']);
        });

        Schema::create('tbl_template_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('template_id')->references('id')->on('tbl_templates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['template_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_template_likes');
        Schema::dropIfExists('tbl_template_uses');

        Schema::table('tbl_templates', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropIndex(['is_user_created', 'trending_score']);
            $table->dropColumn(['creator_id', 'is_user_created', 'source_post_id', 'trending_score', 'like_count']);
        });
    }
};
