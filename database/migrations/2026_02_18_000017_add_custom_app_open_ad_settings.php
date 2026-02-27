<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('custom_app_open_ad_enabled')->default(false)->after('admob_app_open_ios');
            $table->unsignedBigInteger('custom_app_open_ad_post_id')->nullable()->after('custom_app_open_ad_enabled');
            $table->integer('custom_app_open_ad_skip_seconds')->default(5)->after('custom_app_open_ad_post_id');
            $table->string('custom_app_open_ad_url', 500)->nullable()->after('custom_app_open_ad_skip_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'custom_app_open_ad_enabled',
                'custom_app_open_ad_post_id',
                'custom_app_open_ad_skip_seconds',
                'custom_app_open_ad_url',
            ]);
        });
    }
};
