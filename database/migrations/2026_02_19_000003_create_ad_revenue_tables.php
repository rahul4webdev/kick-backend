<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track ad impressions per post/creator
        Schema::create('tbl_ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('creator_id'); // content creator who earns
            $table->unsignedBigInteger('viewer_id')->nullable(); // who saw the ad
            $table->string('ad_type', 30); // banner, interstitial, rewarded, native, preroll, midroll, postroll
            $table->string('ad_network', 30)->default('admob'); // admob, meta, unity, applovin, ima
            $table->decimal('estimated_revenue', 10, 6)->default(0); // USD micro-amount
            $table->string('platform', 10)->default('android'); // android, ios
            $table->timestamps();

            $table->index(['creator_id', 'created_at']);
            $table->index(['post_id', 'created_at']);
        });

        // Monthly revenue payout records
        Schema::create('tbl_ad_revenue_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_impressions')->default(0);
            $table->decimal('total_estimated_revenue', 12, 4)->default(0); // total USD
            $table->decimal('creator_share', 12, 4)->default(0); // creator's USD share
            $table->decimal('platform_share', 12, 4)->default(0); // platform's USD share
            $table->integer('coins_credited')->default(0); // coins credited to creator
            $table->unsignedBigInteger('transaction_id')->nullable(); // link to tbl_coin_transactions
            $table->smallInteger('status')->default(0); // 0=pending, 1=processed, 2=paid
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'period_start', 'period_end']);
        });

        // Revenue share program enrollment
        Schema::create('tbl_ad_revenue_enrollment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->smallInteger('status')->default(0); // 0=pending, 1=approved, 2=rejected
            $table->integer('min_followers_at_enrollment')->default(0);
            $table->integer('min_views_at_enrollment')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ad_revenue_enrollment');
        Schema::dropIfExists('tbl_ad_revenue_payouts');
        Schema::dropIfExists('tbl_ad_impressions');
    }
};
