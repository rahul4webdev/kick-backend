<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(10.00)->after('min_redeem_coins');
            $table->integer('min_followers_for_monetization')->default(1000)->after('commission_percentage');
            $table->integer('reward_coins_per_ad')->default(5)->after('min_followers_for_monetization');
            $table->integer('max_rewarded_ads_daily')->default(10)->after('reward_coins_per_ad');
            $table->string('admob_rewarded_android', 255)->nullable()->after('admob_int');
            $table->string('admob_rewarded_ios', 255)->nullable()->after('admob_int_ios');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'commission_percentage',
                'min_followers_for_monetization',
                'reward_coins_per_ad',
                'max_rewarded_ads_daily',
                'admob_rewarded_android',
                'admob_rewarded_ios',
            ]);
        });
    }
};
