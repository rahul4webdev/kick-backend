<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campaigns created by brands/businesses
        Schema::create('tbl_marketplace_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('brand_user_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->integer('budget_coins')->default(0);
            $table->integer('min_followers')->default(0);
            $table->integer('min_posts')->default(0);
            $table->string('content_type', 50)->nullable(); // reel, video, image, story
            $table->text('requirements')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->smallInteger('status')->default(1); // 1=draft, 2=active, 3=paused, 4=completed, 5=cancelled
            $table->integer('max_creators')->default(0); // 0=unlimited
            $table->integer('accepted_count')->default(0);
            $table->integer('application_count')->default(0);
            $table->timestamp('deadline')->nullable();
            $table->timestampsTz();

            $table->foreign('brand_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['status', 'created_at']);
            $table->index('brand_user_id');
        });

        // Proposals/Applications between brands and creators
        Schema::create('tbl_marketplace_proposals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('brand_user_id');
            $table->unsignedBigInteger('creator_user_id');
            $table->smallInteger('initiated_by')->default(1); // 1=brand invited, 2=creator applied
            $table->text('message')->nullable();
            $table->integer('offered_coins')->default(0);
            $table->smallInteger('status')->default(0); // 0=pending, 1=accepted, 2=declined, 3=completed, 4=cancelled
            $table->text('brand_note')->nullable();
            $table->text('creator_note')->nullable();
            $table->unsignedBigInteger('deliverable_post_id')->nullable();
            $table->timestampsTz();

            $table->foreign('campaign_id')->references('id')->on('tbl_marketplace_campaigns')->onDelete('cascade');
            $table->foreign('brand_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('creator_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['campaign_id', 'creator_user_id']);
            $table->index(['creator_user_id', 'status']);
            $table->index(['brand_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_marketplace_proposals');
        Schema::dropIfExists('tbl_marketplace_campaigns');
    }
};
