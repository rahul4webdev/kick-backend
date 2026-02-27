<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add draft_date and best_time_suggestion to tbl_post
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->date('draft_date')->nullable()->after('scheduled_at');
            $table->time('best_time_suggestion')->nullable()->after('draft_date');
        });

        // Create posting analytics table for best-time-to-post calculations
        Schema::create('tbl_posting_analytics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->integer('hour_of_day'); // 0-23
            $table->integer('day_of_week'); // 0=Sunday, 6=Saturday
            $table->float('avg_views')->default(0);
            $table->float('avg_likes')->default(0);
            $table->float('avg_engagement_rate')->default(0);
            $table->integer('sample_count')->default(0);
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['user_id', 'hour_of_day', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn(['draft_date', 'best_time_suggestion']);
        });
        Schema::dropIfExists('tbl_posting_analytics');
    }
};
