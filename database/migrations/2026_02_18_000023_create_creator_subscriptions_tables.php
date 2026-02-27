<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription tiers defined by each creator
        Schema::create('tbl_subscription_tiers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->string('name', 100);           // e.g. "Basic", "Premium", "VIP"
            $table->integer('price_coins');          // monthly price in coins
            $table->text('description')->nullable();
            $table->jsonb('benefits')->nullable();   // ["Exclusive content", "Badge", "Early access"]
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
        });

        // Active subscriptions (subscriber → creator via tier)
        Schema::create('tbl_creator_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('subscriber_id')->index();
            $table->bigInteger('creator_id')->index();
            $table->bigInteger('tier_id');
            $table->integer('price_coins');          // price at time of subscription
            $table->smallInteger('status')->default(1); // 1=active, 2=cancelled, 3=expired
            $table->boolean('auto_renew')->default(true);
            $table->timestampTz('started_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->foreign('subscriber_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('tier_id')->references('id')->on('tbl_subscription_tiers')->onDelete('cascade');

            // One active subscription per subscriber-creator pair
            $table->unique(['subscriber_id', 'creator_id']);
        });

        // Add subscriber-only visibility to posts
        // postVisibility: 0=public, 1=followers, 2=onlyMe, 3=subscribers
        // Also add subscriber_count to users
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->integer('subscriber_count')->default(0);
            $table->boolean('subscriptions_enabled')->default(false);
        });

        // Add subscriber-only post flag
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->boolean('is_subscriber_only')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn('is_subscriber_only');
        });
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['subscriber_count', 'subscriptions_enabled']);
        });
        Schema::dropIfExists('tbl_creator_subscriptions');
        Schema::dropIfExists('tbl_subscription_tiers');
    }
};
