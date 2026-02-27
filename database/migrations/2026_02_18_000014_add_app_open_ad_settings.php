<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('app_open_ad_enabled')->default(false)->after('admob_ios_status');
            $table->string('admob_app_open_android', 255)->nullable()->after('app_open_ad_enabled');
            $table->string('admob_app_open_ios', 255)->nullable()->after('admob_app_open_android');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'app_open_ad_enabled',
                'admob_app_open_android',
                'admob_app_open_ios',
            ]);
        });
    }
};
