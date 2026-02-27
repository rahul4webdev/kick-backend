<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->string('analytics_api_key', 200)->nullable();
            $table->string('analytics_base_url', 200)->default('http://127.0.0.1:3001');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['analytics_api_key', 'analytics_base_url']);
        });
    }
};
