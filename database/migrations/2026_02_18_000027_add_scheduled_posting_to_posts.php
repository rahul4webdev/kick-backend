<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            // post_status: 1=published (default), 2=scheduled, 3=failed
            $table->smallInteger('post_status')->default(1);
            $table->timestampTz('scheduled_at')->nullable();
        });

        // Index for the cron job query
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->index(['post_status', 'scheduled_at'], 'idx_post_scheduled');
        });
    }

    public function down()
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropIndex('idx_post_scheduled');
            $table->dropColumn(['post_status', 'scheduled_at']);
        });
    }
};
