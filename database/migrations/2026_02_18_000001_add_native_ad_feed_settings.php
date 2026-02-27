<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('native_ad_feed_enabled')->default(false)->after('ima_postroll_ad_tag_ios');
            $table->string('admob_native_android', 255)->nullable()->after('native_ad_feed_enabled');
            $table->string('admob_native_ios', 255)->nullable()->after('admob_native_android');
            $table->integer('native_ad_min_interval')->default(4)->after('admob_native_ios');
            $table->integer('native_ad_max_interval')->default(8)->after('native_ad_min_interval');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'native_ad_feed_enabled',
                'admob_native_android', 'admob_native_ios',
                'native_ad_min_interval', 'native_ad_max_interval',
            ]);
        });
    }
};
