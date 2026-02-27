<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->decimal('ecpm_rate', 8, 2)->default(2.00);
            $table->integer('creator_revenue_share')->default(55); // 55% to creator
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['ecpm_rate', 'creator_revenue_share']);
        });
    }
};
