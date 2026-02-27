<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('live_feed_url', 500)->nullable();
            $table->string('live_feed_logo', 500)->nullable();
            $table->string('channel_name', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['live_feed_url', 'live_feed_logo', 'channel_name']);
        });
    }
};
